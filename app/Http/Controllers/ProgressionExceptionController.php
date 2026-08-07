<?php

namespace App\Http\Controllers;

use App\Http\Requests\RejectProgressionExceptionRequest;
use App\Http\Requests\StoreProgressionExceptionRequest;
use App\Models\GradeLevel;
use App\Models\ProgressionException;
use App\Models\StudentEnrollment;

class ProgressionExceptionController extends Controller
{
    public function index()
    {
        $pending = ProgressionException::query()
            ->where('status', 'pending')
            ->with(['student', 'enrollment.gradeLevel', 'requestedBy'])
            ->latest()
            ->get();

        $resolved = ProgressionException::query()
            ->where('status', '!=', 'pending')
            ->with(['student', 'enrollment.gradeLevel', 'requestedBy', 'reviewedBy'])
            ->latest('reviewed_at')
            ->limit(50)
            ->get();

        return view('curriculum.progression.exceptions.index', compact('pending', 'resolved'));
    }

    public function create(GradeLevel $gradeLevel)
    {
        $enrollments = StudentEnrollment::query()
            ->where('grade_level_id', $gradeLevel->id)
            ->where('status', 'active')
            ->whereDoesntHave('progressionException', fn ($q) => $q->where('status', 'pending'))
            ->with('student')
            ->get()
            ->sortBy(fn ($e) => $e->student->full_name)
            ->values();

        return view('curriculum.progression.exceptions.create', compact('gradeLevel', 'enrollments'));
    }

    public function store(StoreProgressionExceptionRequest $request, GradeLevel $gradeLevel)
    {
        $validated = $request->validated();
        $enrollment = StudentEnrollment::query()->findOrFail($validated['enrollment_id']);

        ProgressionException::query()->create([
            'user_id'           => $enrollment->user_id,
            'enrollment_id'     => $enrollment->id,
            'type'              => $validated['type'],
            'reason'            => $validated['reason'],
            'new_academic_year' => $validated['new_academic_year'] ?? null,
            'status'            => 'pending',
            'requested_by'      => $request->user()->id,
        ]);

        return redirect()->route('curriculum.progression.show', $gradeLevel->id)
            ->with('success', 'Exception request submitted for approval.');
    }

    public function approve(ProgressionException $exception)
    {
        abort_unless(request()->user()?->hasPermission('progression.approve'), 403);
        abort_unless($exception->status === 'pending', 422, 'This exception has already been decided.');

        $enrollment = $exception->enrollment;

        if ($exception->type === 'repeat') {
            $enrollment->update(['status' => 'repeated', 'exited_on' => now()]);

            StudentEnrollment::query()->create([
                'user_id'        => $enrollment->user_id,
                'grade_level_id' => $enrollment->grade_level_id, // same grade again
                'stream_id'      => $enrollment->stream_id,       // keep same stream by default
                'pathway_id'     => $enrollment->pathway_id,      // repeating doesn't reset pathway choice
                'academic_year'  => $exception->new_academic_year,
                'status'         => 'active',
                'enrolled_on'    => now(),
            ]);
        } else {
            // transferred_out, withdrawn, deceased — all exit without a new enrollment
            $enrollment->update(['status' => $exception->type, 'exited_on' => now()]);
        }

        $exception->update(['status' => 'approved', 'reviewed_by' => request()->user()->id, 'reviewed_at' => now()]);

        return back()->with('success', "Exception approved: {$exception->student->full_name} \u{2014} {$exception->typeLabel()}.");
    }

    public function reject(RejectProgressionExceptionRequest $request, ProgressionException $exception)
    {
        abort_unless($exception->status === 'pending', 422, 'This exception has already been decided.');

        $exception->update([
            'status'       => 'rejected',
            'reviewed_by'  => $request->user()->id,
            'reviewed_at'  => now(),
            'review_notes' => $request->validated('review_notes'),
        ]);

        return back()->with('success', "Exception rejected: {$exception->student->full_name} remains in the standard progression path.");
    }
}
