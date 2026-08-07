<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePromotionRequest;
use App\Models\GradeLevel;
use App\Models\ProgressionException;
use App\Models\StudentEnrollment;
use App\Services\Curriculum\ProgressionEligibilityService;
use Illuminate\Support\Facades\DB;

class ProgressionController extends Controller
{
    public function __construct(private ProgressionEligibilityService $eligibility) {}

    public function index()
    {
        $activeCounts = StudentEnrollment::query()->where('status', 'active')
            ->selectRaw('grade_level_id, COUNT(*) as total')->groupBy('grade_level_id')->pluck('total', 'grade_level_id');

        $pendingExceptionEnrollmentIds = ProgressionException::query()->where('status', 'pending')->pluck('enrollment_id');
        $pendingCounts = StudentEnrollment::query()->whereIn('id', $pendingExceptionEnrollmentIds)
            ->selectRaw('grade_level_id, COUNT(*) as total')->groupBy('grade_level_id')->pluck('total', 'grade_level_id');

        $gradeLevels = GradeLevel::query()->with('educationLevel')->orderBy('sequence')->get()
            ->map(function ($grade) use ($activeCounts, $pendingCounts) {
                $grade->active_student_count = $activeCounts[$grade->id] ?? 0;
                $grade->pending_exception_count = $pendingCounts[$grade->id] ?? 0;
                return $grade;
            });

        return view('curriculum.progression.index', compact('gradeLevels'));
    }

    public function show(GradeLevel $gradeLevel)
    {
        $pendingExceptionEnrollmentIds = ProgressionException::query()->where('status', 'pending')->pluck('enrollment_id');

        $activeEnrollments = StudentEnrollment::query()
            ->where('grade_level_id', $gradeLevel->id)
            ->where('status', 'active')
            ->whereNotIn('id', $pendingExceptionEnrollmentIds)
            ->with(['student', 'stream', 'pathway'])
            ->get();

        $academicYear = $activeEnrollments->countBy('academic_year')->sortDesc()->keys()->first() ?? (string) date('Y');
        $window = $this->eligibility->windowStatus($academicYear);

        $eligible = collect();
        $ineligible = collect();

        foreach ($activeEnrollments as $enrollment) {
            if ($this->eligibility->hasCompletedAllTerms($enrollment->student, $enrollment->academic_year)) {
                $eligible->push($enrollment);
            } else {
                $enrollment->missing_terms = $this->eligibility->missingTerms($enrollment->student, $enrollment->academic_year);
                $ineligible->push($enrollment);
            }
        }

        $nextGradeLevel = $gradeLevel->nextGradeLevel();
        $enteringSeniorSecondary = $nextGradeLevel && $nextGradeLevel->isSeniorSecondaryEntryGrade() && ! $gradeLevel->isSeniorSecondary();
        $carryingPathwayForward = $nextGradeLevel && $nextGradeLevel->isSeniorSecondary() && $gradeLevel->isSeniorSecondary();

        return view('curriculum.progression.show', [
            'gradeLevel'              => $gradeLevel,
            'nextGradeLevel'          => $nextGradeLevel,
            'academicYear'            => $academicYear,
            'window'                  => $window,
            'eligible'                => $eligible->sortBy(fn ($e) => $e->student->full_name)->values(),
            'ineligible'              => $ineligible->sortBy(fn ($e) => $e->student->full_name)->values(),
            'enteringSeniorSecondary' => $enteringSeniorSecondary,
            'carryingPathwayForward'  => $carryingPathwayForward,
        ]);
    }

    /**
     * The default, 100%-of-students path: promote everyone eligible to the
     * next grade (or graduate them if this is the final grade). Students
     * with a pending exception or incomplete term results are excluded
     * automatically — no per-student decision needed for the normal case.
     * Stream is carried forward by name (e.g. PP1 Main -> PP2 Main); if no
     * stream with the same name exists in the next grade, the student is
     * left unassigned to a stream and flagged in the summary.
     */
    public function promoteAll(StorePromotionRequest $request, GradeLevel $gradeLevel)
    {
        $validated = $request->validated();
        $nextGradeLevel = $gradeLevel->nextGradeLevel();

        $enteringSeniorSecondary = $nextGradeLevel && $nextGradeLevel->isSeniorSecondaryEntryGrade() && ! $gradeLevel->isSeniorSecondary();
        if ($enteringSeniorSecondary) {
            return back()->with('error', 'Students entering Senior Secondary must be classified into a pathway first — use "Classify Pathways & Promote" instead.');
        }

        $pendingExceptionEnrollmentIds = ProgressionException::query()->where('status', 'pending')->pluck('enrollment_id');
        $activeEnrollments = StudentEnrollment::query()
            ->where('grade_level_id', $gradeLevel->id)
            ->where('status', 'active')
            ->whereNotIn('id', $pendingExceptionEnrollmentIds)
            ->with(['student', 'stream'])
            ->get();

        $academicYear = $activeEnrollments->countBy('academic_year')->sortDesc()->keys()->first() ?? (string) date('Y');
        $window = $this->eligibility->windowStatus($academicYear);

        if (! $window['open']) {
            return back()->with('error', 'Progression window is closed: ' . $window['reason']);
        }

        $carryingPathwayForward = $nextGradeLevel && $nextGradeLevel->isSeniorSecondary() && $gradeLevel->isSeniorSecondary();

        $nextGradeLevelStreamsByName = $nextGradeLevel
            ? $nextGradeLevel->streams()->get()->keyBy(fn ($s) => strtolower($s->name))
            : collect();

        $promoted = 0;
        $skippedResults = 0;
        $unmatchedStreams = 0;

        DB::transaction(function () use ($activeEnrollments, $nextGradeLevel, $nextGradeLevelStreamsByName, $validated, $carryingPathwayForward, &$promoted, &$skippedResults, &$unmatchedStreams) {
            foreach ($activeEnrollments as $enrollment) {
                if (! $this->eligibility->hasCompletedAllTerms($enrollment->student, $enrollment->academic_year)) {
                    $skippedResults++;
                    continue;
                }

                if ($nextGradeLevel) {
                    $enrollment->update(['status' => 'promoted', 'exited_on' => now()]);

                    $matchedStream = $enrollment->stream
                        ? $nextGradeLevelStreamsByName->get(strtolower($enrollment->stream->name))
                        : null;

                    if ($enrollment->stream && ! $matchedStream) {
                        $unmatchedStreams++;
                    }

                    StudentEnrollment::query()->create([
                        'user_id'        => $enrollment->user_id,
                        'grade_level_id' => $nextGradeLevel->id,
                        'stream_id'      => $matchedStream?->id,
                        'pathway_id'     => $carryingPathwayForward ? $enrollment->pathway_id : null,
                        'academic_year'  => $validated['new_academic_year'],
                        'status'         => 'active',
                        'enrolled_on'    => now(),
                    ]);
                } else {
                    $enrollment->update(['status' => 'graduated', 'exited_on' => now()]);
                }

                $promoted++;
            }
        });

        $verb = $nextGradeLevel ? 'promoted to ' . $nextGradeLevel->name : 'graduated';
        $message = "{$promoted} student(s) in {$gradeLevel->name} were {$verb}.";
        if ($skippedResults > 0) {
            $message .= " {$skippedResults} student(s) were skipped — incomplete term results.";
        }
        if ($unmatchedStreams > 0) {
            $message .= " {$unmatchedStreams} student(s) had no matching stream in {$nextGradeLevel->name} and were left unassigned.";
        }

        return redirect()->route('curriculum.progression.index')->with('success', $message);
    }
}
