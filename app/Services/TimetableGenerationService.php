<?php

namespace App\Services;

use App\Models\GradeLevel;
use App\Models\LessonRequirement;
use App\Models\Stream;
use App\Models\SubjectTeacherAssignment;
use App\Models\TimeSlot;
use App\Models\Timetable;
use App\Models\TimetableEntry;
use App\Models\TimetableSlotGroupSelection;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TimetableGenerationService
{
    /** @var array<string, array<string, true>> teacherId => "day-slotId" => true */
    private array $teacherBusy = [];

    /** @var array<string, array<string, true>> streamId => "day-slotId" => true */
    private array $streamBusy = [];

    /**
     * @var array<int, array<int, array{learning_area_id: string, stream_ids: array, is_double: bool}>>
     * day => sequence => entry summary, keyed by SEQUENCE.
     */
    private array $entriesByDaySequence = [];

    /** @var array<string, array<int, array<string, int>>> streamId => day => learningAreaId => count-that-day */
    private array $subjectDayUsage = [];

    /** @var array<string, array<int, int>> streamId => day => periods already scheduled that day (for load balancing) */
    private array $streamDayLoad = [];

    /** @var array<string, Collection> groupId => Collection<day => slots (sorted, values())> */
    private array $slotsByDayCache = [];

    private array $conflicts = [];
    private int $placedCount = 0;

    /** @var array<string, Collection> gradeLevelId => Collection<learning_area_id => LessonRequirement> */
    private array $requirementsByGrade = [];

    public function generate(Timetable $timetable): array
    {
        $this->teacherBusy = [];
        $this->streamBusy = [];
        $this->entriesByDaySequence = [];
        $this->subjectDayUsage = [];
        $this->streamDayLoad = [];
        $this->slotsByDayCache = [];
        $this->conflicts = [];
        $this->requirementsByGrade = [];
        $this->placedCount = 0;

        DB::transaction(function () use ($timetable) {
            $timetable->entries()->where('source', 'generated')->where('locked', false)->delete();
            $this->reserveExistingEntries($timetable);

           $sessions = $this->buildSessionRequests($timetable);
            $sessions = $sessions->sort(function ($a, $b) {
                return [$b['is_double'], count($b['stream_ids'])] <=> [$a['is_double'], count($a['stream_ids'])];
            })->values();

            $groupCache = [];

            foreach ($sessions as $session) {
                $groupId = $this->groupIdForGrade($timetable, $session['grade_level_id'], $groupCache);

                if (! $groupId) {
                    $this->conflicts[] = "No time slot group selected for {$session['learning_area']->name} ({$session['grade_level_id']}) — set one on the timetable's setup.";
                    continue;
                }

                $slotsByDay = $this->slotsByDayForGroup($groupId);
                if ($slotsByDay->isEmpty()) {
                    $this->conflicts[] = "The time slot group for {$session['learning_area']->name} has no active main slots defined.";
                    continue;
                }

                $placed = $session['is_double']
                    ? $this->placeDouble($timetable, $session, $slotsByDay)
                    : $this->placeSingle($timetable, $session, $slotsByDay);

                if (! $placed) {
                    $this->conflicts[] = sprintf(
                        'Could not place %s%s for %s (%s) — no slot available under the current spacing rules: %s',
                        $session['learning_area']->name,
                        $session['is_double'] ? ' (double)' : '',
                        implode(' & ', $session['stream_names']),
                        $session['teacher_name'],
                        implode(', ', $session['stream_names'])
                    );
                }
            }

            $timetable->update([
                'generated_at' => now(),
                'generated_by' => auth()->id(),
                'generation_notes' => [
                    'placed' => $this->placedCount,
                    'unplaced' => count($this->conflicts),
                    'conflicts' => $this->conflicts,
                    'run_at' => now()->toDateTimeString(),
                ],
            ]);
        });

        return $this->conflicts;
    }

    private function reserveExistingEntries(Timetable $timetable): void
    {
        $existing = $timetable->entries()->with(['timeSlot', 'streams'])->get();

        foreach ($existing as $entry) {
            $day = $entry->timeSlot->day_of_week;
            $sequence = $entry->timeSlot->sequence;
            $streamIds = $entry->streams->pluck('id')->all();

            if ($entry->teacher_id) {
                $this->teacherBusy[$entry->teacher_id][$day.'-'.$entry->time_slot_id] = true;
            }
            foreach ($streamIds as $streamId) {
                $this->streamBusy[$streamId][$day.'-'.$entry->time_slot_id] = true;
            }

            $this->entriesByDaySequence[$day][$sequence] = [
                'learning_area_id' => $entry->learning_area_id,
                'stream_ids' => $streamIds,
                'is_double' => (bool) $entry->is_double,
            ];

            foreach ($streamIds as $streamId) {
                $this->subjectDayUsage[$streamId][$day][$entry->learning_area_id] =
                    ($this->subjectDayUsage[$streamId][$day][$entry->learning_area_id] ?? 0) + 1;
                $this->streamDayLoad[$streamId][$day] = ($this->streamDayLoad[$streamId][$day] ?? 0) + 1;
            }
        }
    }

    private function buildSessionRequests(Timetable $timetable)/*: Collection*/
    {
        $gradeLevelIds = $timetable->gradeLevels->pluck('id');
//        $streams = Stream::query()->whereIn('grade_level_id', $gradeLevelIds)
//            ->with(['gradeLevel.educationLevel', 'pathway'])->get();
       $streams = Stream::query()->whereIn('grade_level_id', $gradeLevelIds)
            ->with(['gradeLevel.educationLevel', 'pathway.learningAreas'])->get();

        $groups = collect();

        foreach ($streams as $stream) {

            $learningAreas = $this->resolveLearningAreasForStream($stream);

            foreach ($learningAreas as $learningArea) {
                $assignment = SubjectTeacherAssignment::query()
                    ->where('stream_id', $stream->id)
                    ->where('learning_area_id', $learningArea->id)
                    ->where('academic_year', $timetable->academic_year)
                    ->where('status', 'active')
                    ->first();

                if (! $assignment) {
                    $this->conflicts[] = "No active teacher assigned for {$learningArea->name} in {$stream->full_name} — skipped.";
                    continue;
                }

/*                $requirement = LessonRequirement::query()
                    ->where('grade_level_id', $stream->grade_level_id)
                    ->where('learning_area_id', $learningArea->id)
                    ->first();

                $lessonsPerWeek = $requirement->lessons_per_week ?? 5;
                $doublesPerWeek = $requirement->double_lessons_per_week ?? 0;*/

                $requirement = $this->requirementsForGrade($stream->grade_level_id)->get($learningArea->id);

                if (! $requirement) {
                    continue; // not ticked for this grade — never schedule it (and never default to 5 lessons)
                }

                $lessonsPerWeek = (int) $requirement->lessons_per_week;
                $doublesPerWeek = (int) ($requirement->double_lessons_per_week ?? 0);

                $key = $assignment->user_id.'|'.$learningArea->id.'|'.$stream->grade_level_id;

                if ($groups->has($key)) {
                    $groups[$key]['streams']->push($stream);
                } else {
                    $groups[$key] = [
                        'teacher_id' => $assignment->user_id,
                        'assignment_id' => $assignment->id,
                        'learning_area' => $learningArea,
                        'grade_level_id' => $stream->grade_level_id,
                        'lessons_per_week' => $lessonsPerWeek,
                        'double_lessons_per_week' => $doublesPerWeek,
                        'streams' => collect([$stream]),
                    ];
                }
            }
        }

        $sessions = collect();

        foreach ($groups as $group) {
            $streamIds = $group['streams']->pluck('id')->sort()->values();

            $alreadyPlaced = TimetableEntry::query()
                ->where('timetable_id', $timetable->id)
                ->where('learning_area_id', $group['learning_area']->id)
                ->where('teacher_id', $group['teacher_id'])
                ->whereHas('streams', fn ($q) => $q->whereIn('stream_id', $streamIds), '=', $streamIds->count())
                ->get();

            $lessonsRemaining = $group['lessons_per_week'] - $alreadyPlaced->sum(fn ($e) => $e->is_double ? 2 : 1);
            $doublesRemaining = max(0, $group['double_lessons_per_week'] - $alreadyPlaced->where('is_double', true)->count());
            $singlesRemaining = max(0, $lessonsRemaining - ($doublesRemaining * 2));

            $teacherName = trim(User::find($group['teacher_id'])?->first_name.' '.User::find($group['teacher_id'])?->last_name);

            for ($i = 0; $i < $doublesRemaining; $i++) {
                $sessions->push($this->sessionPayload($group, $streamIds, $teacherName, true));
            }
            for ($i = 0; $i < $singlesRemaining; $i++) {
                $sessions->push($this->sessionPayload($group, $streamIds, $teacherName, false));
            }
        }

        return $sessions;
    }

    private function sessionPayload(array $group, Collection $streamIds, string $teacherName, bool $isDouble): array
    {
        return [
            'teacher_id' => $group['teacher_id'],
            'teacher_name' => $teacherName,
            'assignment_id' => $group['assignment_id'],
            'learning_area' => $group['learning_area'],
            'grade_level_id' => $group['grade_level_id'],
            'stream_ids' => $streamIds->all(),
            'stream_names' => $group['streams']->pluck('full_name')->all(),
            'is_double' => $isDouble,
        ];
    }


    /** @var int|null cached once per generation run */
    private ?int $pathwayThresholdSequence = null;

    /**
     * Below the G10 threshold: every learning area linked to the grade, unrestricted.
     * G10 and above: only the grade's compulsory learning areas PLUS this specific
     * stream's pathway subjects. A stream with no pathway at this level is a data
     * problem, not a reason to silently schedule the entire subject catalog — it's
     * surfaced as a conflict instead.
     */

    /** Lesson requirements ("ticked" subjects) for a grade, keyed by learning_area_id. Cached per run. */
    private function requirementsForGrade(string $gradeLevelId): Collection
    {
        return $this->requirementsByGrade[$gradeLevelId] ??= LessonRequirement::query()
            ->where('grade_level_id', $gradeLevelId)
            ->get()
            ->keyBy('learning_area_id');
    }

    /** Adds a conflict once, so per-stream checks don't repeat the same message. */
    private function conflict(string $message): void
    {
        if (! in_array($message, $this->conflicts, true)) {
            $this->conflicts[] = $message;
        }
    }

    /**
     * What gets timetabled for a stream — always limited to THE GRADE'S OWN learning areas:
     *
     *  1. Candidates = areas linked to the grade (grade_level_learning_area) that are ticked
     *     on the grade's Lesson Requirements page (a LessonRequirement row exists).
     *  2. Below the G10 threshold: that's the whole list.
     *  3. G10 and above: only those that are compulsory OR belong to this stream's pathway.
     *     (pathway_learning_area has no grade, so a pathway alone would also pull in the
     *     subjects of other grades — the grade filter above is what prevents that.)
     */
    private function resolveLearningAreasForStream(Stream $stream): Collection
    {
        $grade        = $stream->gradeLevel;
        $requirements = $this->requirementsForGrade($grade->id);

        $areas = $grade->learningAreas
            ->filter(fn ($area) => $area->status !== 'inactive')
            ->filter(fn ($area) => $requirements->has($area->id))
            ->values();

        if ($areas->isEmpty()) {
            $this->conflict("{$grade->name} has no learning areas ticked on its Lesson Requirements — nothing to schedule for it.");
            return $areas;
        }

        if (! $this->requiresPathway($grade)) {
            return $areas;
        }

        if (! $stream->pathway) {
            $this->conflict("{$stream->full_name} has no pathway assigned — only its compulsory subjects were scheduled.");
        }

        $pathwayIds = $stream->pathway?->learningAreas->pluck('id') ?? collect();

        return $areas
            ->filter(fn ($area) => $area->is_compulsory || $pathwayIds->contains($area->id))
            ->values();
    }

    /** Grade 10 and above, by sequence — same threshold pattern used in the student-import controller (`GradeLevel::where('code', 'G10')`), not the education-level code, which isn't reliable here. */
    private function requiresPathway(GradeLevel $grade): bool
    {
        if ($this->pathwayThresholdSequence === null) {
            $this->pathwayThresholdSequence = GradeLevel::query()->where('code', 'G10')->value('sequence');
        }

        return $this->pathwayThresholdSequence !== null && $grade->sequence >= $this->pathwayThresholdSequence;
    }

    private function placeSingle(Timetable $timetable, array $session, Collection $slotsByDay): bool
    {
        $days = $this->orderedDays($slotsByDay, $session['stream_ids'], $session['learning_area']->id);

        foreach ($days as $day) {
            $slots = $slotsByDay[$day]; // already ->values()'d in slotsByDayForGroup()

            foreach ($slots as $i => $slot) {
                if ($slot->is_break) continue;
                if (! $this->isFree($session, $day, $slot->id)) continue;

                [$prevSeq, $nextSeq] = $this->logicalNeighbors($slots, $i);
                if ($this->hasSameSubjectAt($day, $prevSeq, $session['learning_area']->id, $session['stream_ids'])) continue;
                if ($this->hasSameSubjectAt($day, $nextSeq, $session['learning_area']->id, $session['stream_ids'])) continue;

                $this->createEntry($timetable, $session, [$slot], null);
                return true;
            }
        }
        return false;
    }

    private function placeDouble(Timetable $timetable, array $session, Collection $slotsByDay): bool
    {
        $days = $this->orderedDaysForDouble($slotsByDay, $session['stream_ids'], $session['learning_area']->id);

        foreach ($days as $day) {
            $slots = $slotsByDay[$day];
            $candidates = [];

            foreach ($slots as $i => $slot) {
                $next = $slots->get($i + 1);
                if (! $next || $slot->is_break || $next->is_break) continue;
                if ($next->sequence !== $slot->sequence + 1) continue; // the double's two halves must be truly contiguous

                if (! $this->isFree($session, $day, $slot->id) || ! $this->isFree($session, $day, $next->id)) continue;

                // Neighbors of the WHOLE double: nearest teaching period before the
                // first half, and nearest teaching period after the second half —
                // skipping over any break, exactly like a single lesson's neighbors.
                [$prevSeq, ] = $this->logicalNeighbors($slots, $i);
                [, $afterSeq] = $this->logicalNeighbors($slots, $i + 1);

                if ($this->hasDoubleAt($day, $prevSeq, $session['stream_ids'])) continue;
                if ($this->hasDoubleAt($day, $afterSeq, $session['stream_ids'])) continue;
                if ($this->hasSameSubjectAt($day, $prevSeq, $session['learning_area']->id, $session['stream_ids'])) continue;
                if ($this->hasSameSubjectAt($day, $afterSeq, $session['learning_area']->id, $session['stream_ids'])) continue;

                $candidates[] = [$slot, $next];
            }

            if (empty($candidates)) continue;

            // Pick randomly among every valid pair for this day — not always the earliest one.
            shuffle($candidates);
            [$slot, $next] = $candidates[0];

            $groupId = (string) Str::uuid();
            $this->createEntry($timetable, $session, [$slot, $next], $groupId);
            return true;
        }
        return false;
    }

    /**
     * Given a day's slot list (sorted by sequence, breaks included) and the index
     * of a slot, finds the SEQUENCE of the nearest real teaching period before and
     * after it — skipping over break rows entirely. A break in between two periods
     * does NOT make them "not adjacent"; only a genuine gap (no slot defined) does.
     * Returns null for a side with no such neighbor (start/end of day).
     */
    private function logicalNeighbors(Collection $slots, int $index): array
    {
        $prevSeq = null;
        for ($j = $index - 1; $j >= 0; $j--) {
            $candidate = $slots->get($j);
            if (! $candidate) break;
            if ($candidate->is_break) continue; // skip over the break, keep looking
            $prevSeq = $candidate->sequence;
            break;
        }

        $nextSeq = null;
        $count = $slots->count();
        for ($j = $index + 1; $j < $count; $j++) {
            $candidate = $slots->get($j);
            if (! $candidate) break;
            if ($candidate->is_break) continue;
            $nextSeq = $candidate->sequence;
            break;
        }

        return [$prevSeq, $nextSeq];
    }

    /** Day ordering for singles: subject-fresh days first, then lightest-loaded. */
    private function orderedDays(Collection $slotsByDay, array $streamIds, string $learningAreaId): array
    {
        $days = $slotsByDay->keys()->all();

        usort($days, function ($a, $b) use ($streamIds, $learningAreaId) {
            $usedA = $this->subjectUsedOnDay($a, $learningAreaId, $streamIds) ? 1 : 0;
            $usedB = $this->subjectUsedOnDay($b, $learningAreaId, $streamIds) ? 1 : 0;
            if ($usedA !== $usedB) return $usedA <=> $usedB;

            $loadA = $this->dayLoad($a, $streamIds);
            $loadB = $this->dayLoad($b, $streamIds);
            if ($loadA !== $loadB) return $loadA <=> $loadB;

            return $a <=> $b;
        });

        return $days;
    }

    /**
     * Day ordering for doubles: no cap on doubles-per-day anymore — a day can take
     * more than one double, as long as none of them end up adjacent to each other
     * (that's enforced in placeDouble via hasDoubleAt on logical neighbors, not here).
     * Ties are shuffled so repeated generations don't always favor the same day/slot.
     */
    private function orderedDaysForDouble(Collection $slotsByDay, array $streamIds, string $learningAreaId): array
    {
        $days = $slotsByDay->keys()->all();

        $tiers = [];
        foreach ($days as $day) {
            $used = $this->subjectUsedOnDay($day, $learningAreaId, $streamIds) ? 1 : 0;
            $load = $this->dayLoad($day, $streamIds);
            $tiers[$used][$load][] = $day;
        }

        ksort($tiers);
        $ordered = [];
        foreach ($tiers as $loadGroups) {
            ksort($loadGroups);
            foreach ($loadGroups as $dayList) {
                shuffle($dayList);
                array_push($ordered, ...$dayList);
            }
        }

        return $ordered;
    }

    private function subjectUsedOnDay(int $day, string $learningAreaId, array $streamIds): bool
    {
        foreach ($streamIds as $streamId) {
            if (($this->subjectDayUsage[$streamId][$day][$learningAreaId] ?? 0) > 0) return true;
        }
        return false;
    }

    private function dayLoad(int $day, array $streamIds): int
    {
        $total = 0;
        foreach ($streamIds as $streamId) {
            $total += $this->streamDayLoad[$streamId][$day] ?? 0;
        }
        return $total;
    }

    private function hasSameSubjectAt(int $day, ?int $sequence, string $learningAreaId, array $streamIds): bool
    {
        if ($sequence === null) return false;
        $entry = $this->entriesByDaySequence[$day][$sequence] ?? null;
        if (! $entry) return false;
        return $entry['learning_area_id'] === $learningAreaId && array_intersect($entry['stream_ids'], $streamIds);
    }

    private function hasDoubleAt(int $day, ?int $sequence, array $streamIds): bool
    {
        if ($sequence === null) return false;
        $entry = $this->entriesByDaySequence[$day][$sequence] ?? null;
        if (! $entry) return false;
        return ! empty($entry['is_double']) && array_intersect($entry['stream_ids'], $streamIds);
    }

    private function isFree(array $session, $day, $slotId): bool
    {
        $key = $day.'-'.$slotId;
        if (isset($this->teacherBusy[$session['teacher_id']][$key])) return false;

        foreach ($session['stream_ids'] as $streamId) {
            if (isset($this->streamBusy[$streamId][$key])) return false;
        }
        return true;
    }

    private function createEntry(Timetable $timetable, array $session, array $slots, ?string $groupId): void
    {
        foreach ($slots as $slot) {
            $entry = TimetableEntry::create([
                'timetable_id' => $timetable->id,
                'time_slot_id' => $slot->id,
                'grade_level_id' => $session['grade_level_id'],
                'learning_area_id' => $session['learning_area']->id,
                'subject_teacher_assignment_id' => $session['assignment_id'],
                'teacher_id' => $session['teacher_id'],
                'is_double' => $session['is_double'],
                'double_group_id' => $groupId,
                'locked' => false,
                'source' => 'generated',
            ]);
            $entry->streams()->attach($session['stream_ids']);

            $key = $slot->day_of_week.'-'.$slot->id;
            $this->teacherBusy[$session['teacher_id']][$key] = true;

            $this->entriesByDaySequence[$slot->day_of_week][$slot->sequence] = [
                'learning_area_id' => $session['learning_area']->id,
                'stream_ids' => $session['stream_ids'],
                'is_double' => $session['is_double'],
            ];

            foreach ($session['stream_ids'] as $streamId) {
                $this->streamBusy[$streamId][$key] = true;
                $this->subjectDayUsage[$streamId][$slot->day_of_week][$session['learning_area']->id] =
                    ($this->subjectDayUsage[$streamId][$slot->day_of_week][$session['learning_area']->id] ?? 0) + 1;
                $this->streamDayLoad[$streamId][$slot->day_of_week] =
                    ($this->streamDayLoad[$streamId][$slot->day_of_week] ?? 0) + 1;
            }
        }
        $this->placedCount++;
    }

    private function slotsByDayForGroup(string $groupId): Collection
    {
        if (! isset($this->slotsByDayCache[$groupId])) {
            $this->slotsByDayCache[$groupId] = TimeSlot::query()
                ->where('time_slot_group_id', $groupId)
                ->where('type', 'main')->where('status', 'active')
                ->orderBy('day_of_week')->orderBy('sequence')
                ->get()
                ->groupBy('day_of_week')
                ->map(fn ($slots) => $slots->values()); // indexable 0..n-1 for logicalNeighbors()
        }
        return $this->slotsByDayCache[$groupId];
    }

    private function groupIdForGrade(Timetable $timetable, string $gradeLevelId, array &$cache): ?string
    {
        $eduId = GradeLevel::find($gradeLevelId)?->education_level_id;
        if (! $eduId) return null;

        if (! array_key_exists($eduId, $cache)) {
            $cache[$eduId] = TimetableSlotGroupSelection::where('timetable_id', $timetable->id)
                ->where('education_level_id', $eduId)
                ->value('time_slot_group_id');
        }
        return $cache[$eduId];
    }
}
