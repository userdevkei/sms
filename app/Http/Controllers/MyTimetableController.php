<?php

namespace App\Http\Controllers;

use App\Models\Stream;
use App\Models\StudentEnrollment;
use App\Models\Timetable;
use App\Models\TimetableEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class MyTimetableController extends Controller
{
    /** Route names live here only — change them in one place. */
    private const ROUTE_OWN   = 'timetables.my-timetable.index';
    private const ROUTE_CLASS = 'timetables.my-timetable.class';

    /** Number of colour classes (.tt-c0 … .tt-c11) defined in the view. */
    private const PALETTE_SIZE = 12;

    /* ------------------------------------------------------------------
     | Identity resolution — the ONLY place that decides who counts as a
     | teacher / student. Single `users` table, so their id == users.id.
     ------------------------------------------------------------------ */
    private function teacherId(): ?string
    {
        $user = auth()->user();

        return $user && $user->hasRole('teacher') || $user->hasRole('class_teacher') ? $user->id : null;
    }

    private function studentId(): ?string
    {
        $user = auth()->user();

        return $user && $user->enrollments()->exists() ? $user->id : null;
    }

    /* ------------------------------------------------------------------
     | Helpers
     ------------------------------------------------------------------ */
    private function entryQuery()
    {
        return TimetableEntry::with(['timeSlot', 'learningArea', 'teacher', 'gradeLevel', 'streams.gradeLevel']);
    }

    /** Only published timetables are visible to teachers/students. Newest first. */
    private function publishedTimetables()
    {
        return Timetable::where('status', 'published')
            ->orderByDesc('academic_year')
            ->orderByDesc('term');
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
     * Build a days x times grid.
     *  - columns: distinct time ranges (sorted), shown across the top
     *  - days:    Mon–Fri always, plus any other day that has lessons, shown down the left
     *  - cells:   [day][columnKey] => Collection of entries (usually one)
     */
    private function buildGrid(Collection $entries): array
    {
        // Tag each entry with its grade colour (view-only attribute, never saved).
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
        $key = fn ($e) => $fmt($e->timeSlot->start_time).'-'.$fmt($e->timeSlot->end_time);

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

        $days = collect(range(1, 5))->merge($byDay->keys())->unique()->sort()->values();

        $cells = [];
        foreach ($byDay as $day => $dayEntries) {
            foreach ($dayEntries->groupBy($key) as $colKey => $items) {
                $cells[$day][$colKey] = $items;
            }
        }

        return ['columns' => $columns, 'days' => $days, 'cells' => $cells, 'legend' => $legend];
    }

    /** Pick the requested item by id, falling back to the first. */
    private function pick(Collection $items, ?string $id)
    {
        return ($id ? $items->firstWhere('id', $id) : null) ?? $items->first();
    }

    /** Every view gets the two nav URLs so the Blade never hardcodes route names. */
    private function render(array $data)
    {
        return view('timetables.my', $data + [
                'ownUrl'   => route(self::ROUTE_OWN),
                'classUrl' => route(self::ROUTE_CLASS),
            ]);
    }

    /* ------------------------------------------------------------------
     | GET /my-timetable  — teacher OR student
     ------------------------------------------------------------------ */
    public function index(Request $request)
    {
        if ($teacherId = $this->teacherId()) {
            return $this->teacherView($request, $teacherId);
        }

        if ($studentId = $this->studentId()) {
            return $this->studentView($studentId);
        }

        abort(403, 'No teacher or student profile is linked to your account.');
    }

    private function teacherView(Request $request, string $teacherId)
    {
        $timetables = $this->publishedTimetables()->get();
        $timetable  = $this->pick($timetables, $request->query('timetable'));

        $sections = [];
        if ($timetable) {
            $entries = $this->entryQuery()
                ->where('timetable_id', $timetable->id)
                ->where('teacher_id', $teacherId)
                ->get();

            $sections[] = [
                'label' => $timetable->name,
                'grid'  => $this->buildGrid($entries),
            ];
        }

        return $this->render([
            'title'             => 'My Timetable',
            'action'            => route(self::ROUTE_OWN),
            'sections'          => $sections,
            'timetables'        => $timetables,
            'selectedTimetable' => $timetable,
            'streams'           => collect(),
            'selectedStream'    => null,
            'showClass'         => true,   // teacher needs to know WHICH class
            'showTeacher'       => false,
            'isClassTeacher'    => Stream::where('class_teacher_id', $teacherId)->exists(),
        ]);
    }

    private function studentView(string $userId)
    {
        // Enrollment is YEARLY (one row per academic year). Past years are marked
        // 'promoted', so filtering on status = 'active' would hide every earlier year.
        $enrollments = StudentEnrollment::where('user_id', $userId)->get();

        $sections = [];

        if ($enrollments->isNotEmpty()) {
            // academic_year => [stream ids the student sat in that year]
            $streamIdsByYear = $enrollments
                ->groupBy(fn ($e) => (string) $e->academic_year)
                ->map(fn ($g) => $g->pluck('stream_id')->filter()->unique()->all());

            // Published terms of every year the student was enrolled, but only those that
            // actually contain lessons for the student's stream that year.
            // (Timetable has no direct stream link: timetable -> entries -> entry_streams.)
            $timetables = $this->publishedTimetables()
                ->where(function ($q) use ($streamIdsByYear) {
                    foreach ($streamIdsByYear as $year => $streamIds) {
                        $q->orWhere(fn ($w) => $w
                            ->where('academic_year', $year)
                            ->whereHas('entries.streams', fn ($s) => $s->whereIn('streams.id', $streamIds))
                        );
                    }
                })
                ->get();

            foreach ($timetables as $t) {
                $streamIds = $streamIdsByYear[(string) $t->academic_year] ?? [];

                if (! $streamIds) {
                    continue;
                }

                $entries = $this->entryQuery()
                    ->where('timetable_id', $t->id)
                    ->whereHas('streams', fn ($q) => $q->whereIn('streams.id', $streamIds))
                    ->get();

                $sections[] = [
                    'label' => "Term {$t->term}, {$t->academic_year}",
                    'grid'  => $this->buildGrid($entries),
                ];
            }
        }

        return $this->render([
            'title'             => 'My Timetable',
            'action'            => route(self::ROUTE_OWN),
            'sections'          => $sections,
            'timetables'        => collect(),
            'selectedTimetable' => null,
            'streams'           => collect(),
            'selectedStream'    => null,
            'showClass'         => false,
            'showTeacher'       => true,
            'isClassTeacher'    => false,
        ]);
    }

    /* ------------------------------------------------------------------
     | GET /my-class-timetable  — class teacher
     ------------------------------------------------------------------ */
    public function classTimetable(Request $request)
    {
        $teacherId = $this->teacherId();
        abort_unless($teacherId, 403);

        $streams = Stream::with('gradeLevel')
            ->where('class_teacher_id', $teacherId)
            ->get();

        abort_if($streams->isEmpty(), 403, 'You are not assigned as a class teacher.');

        $stream = $this->pick($streams, $request->query('stream'));

        // Published timetables that cover this stream's grade level.
        $timetables = $this->publishedTimetables()
            ->whereHas('gradeLevels', fn ($q) => $q->where('grade_levels.id', $stream->grade_level_id))
            ->get();

        $timetable = $this->pick($timetables, $request->query('timetable'));

        $sections = [];
        if ($timetable) {
            $entries = $this->entryQuery()
                ->where('timetable_id', $timetable->id)
                ->whereHas('streams', fn ($q) => $q->where('streams.id', $stream->id))
                ->get();

            $sections[] = [
                'label' => $timetable->name,
                'grid'  => $this->buildGrid($entries),
            ];
        }

        return $this->render([
            'title'             => 'Class Timetable — '.trim($stream->gradeLevel?->name.' '.$stream->name),
            'action'            => route(self::ROUTE_CLASS),
            'sections'          => $sections,
            'timetables'        => $timetables,
            'selectedTimetable' => $timetable,
            'streams'           => $streams,
            'selectedStream'    => $stream,
            'showClass'         => false,
            'showTeacher'       => true,
            'isClassTeacher'    => true,
        ]);
    }
}
