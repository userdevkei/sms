<?php

namespace App\Services\Timetable;

use App\Models\StudentEnrollment;
use App\Models\Timetable;
use App\Models\TimetableEntry;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class StudentTimetable
{
    /** Number of colour classes (.tt-c0 … .tt-c11) defined in the view. */
    private const PALETTE_SIZE = 12;

    /**
     * One section per published term the student was enrolled for, newest first.
     * Enrollment is yearly and past years are 'promoted', so we don't filter on status = 'active'.
     */
    public function sectionsFor(User $student): array
    {
        $enrollments = StudentEnrollment::where('user_id', $student->id)->get();

        if ($enrollments->isEmpty()) {
            return [];
        }

        // academic_year => [stream ids the student sat in that year]
        $streamIdsByYear = $enrollments
            ->groupBy(fn ($e) => (string) $e->academic_year)
            ->map(fn ($g) => $g->pluck('stream_id')->filter()->unique()->all());

        // Published terms of every enrolled year that actually contain lessons for the student's stream.
        // (Timetable has no direct stream link: timetable -> entries -> entry_streams.)
        $timetables = Timetable::where('status', 'published')
            ->orderByDesc('academic_year')
            ->orderByDesc('term')
            ->where(function ($q) use ($streamIdsByYear) {
                foreach ($streamIdsByYear as $year => $streamIds) {
                    if (! $streamIds) {
                        continue;
                    }

                    $q->orWhere(fn ($w) => $w
                        ->where('academic_year', $year)
                        ->whereHas('entries.streams', fn ($s) => $s->whereIn('streams.id', $streamIds))
                    );
                }
            })
            ->get();

        $sections = [];

        foreach ($timetables as $t) {
            $streamIds = $streamIdsByYear[(string) $t->academic_year] ?? [];

            if (! $streamIds) {
                continue;
            }

            $entries = TimetableEntry::with(['timeSlot', 'learningArea', 'teacher', 'gradeLevel', 'streams.gradeLevel'])
                ->where('timetable_id', $t->id)
                ->whereHas('streams', fn ($q) => $q->whereIn('streams.id', $streamIds))
                ->get();

            $sections[] = [
                'label' => "Term {$t->term}, {$t->academic_year}",
                'grid'  => $this->buildGrid($entries),
            ];
        }

        return $sections;
    }

    /** Stable colour per grade: by sequence, so adjacent grades never share a colour. */
    private function gradeColorIndex($entry): int
    {
        $seq = $entry->gradeLevel?->sequence;

        return $seq !== null
            ? ((int) $seq) % self::PALETTE_SIZE
            : crc32((string) $entry->grade_level_id) % self::PALETTE_SIZE;
    }

    /**
     * days x times grid:
     *  - columns: distinct time ranges (sorted), across the top
     *  - days:    Mon–Fri always, plus any other day with lessons, down the left
     *  - cells:   [day][columnKey] => Collection of entries
     */
    private function buildGrid(Collection $entries): array
    {
        // View-only attribute, never saved.
        $entries->each(fn ($e) => $e->tt_color = $this->gradeColorIndex($e));

        $legend = $entries
            ->filter(fn ($e) => $e->gradeLevel)
            ->map(fn ($e) => [
                'name'  => $e->gradeLevel->name,
                'seq'   => $e->gradeLevel->sequence,
                'color' => $e->tt_color,
            ])
            ->unique('name')
            ->sortBy('seq')
            ->values();

        $fmt = fn ($t) => Carbon::parse($t)->format('H:i');
        $key = fn ($e) => $fmt($e->timeSlot->start_time) . '-' . $fmt($e->timeSlot->end_time);

        $columns = $entries
            ->map(fn ($e) => [
                'key'   => $key($e),
                'start' => $fmt($e->timeSlot->start_time),
                'end'   => $fmt($e->timeSlot->end_time),
            ])
            ->unique('key')
            ->sortBy('key')
            ->values();

        $byDay = $entries->groupBy(fn ($e) => $e->timeSlot->day_of_week);
        $days  = collect(range(1, 5))->merge($byDay->keys())->unique()->sort()->values();

        $cells = [];
        foreach ($byDay as $day => $dayEntries) {
            foreach ($dayEntries->groupBy($key) as $colKey => $items) {
                $cells[$day][$colKey] = $items;
            }
        }

        return ['columns' => $columns, 'days' => $days, 'cells' => $cells, 'legend' => $legend];
    }
}
