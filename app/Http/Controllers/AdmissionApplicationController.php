<?php

namespace App\Http\Controllers;

use App\Models\AdmissionAnswer;
use App\Models\AdmissionApplication;
use App\Models\AdmissionEvent;
use App\Models\AdmissionLevelSetting;
use App\Models\AdmissionRequirement;
use App\Models\EducationLevel;
use App\Models\Stream;
use App\Services\Admissions\AdmissionMigrationService;
use App\Services\Admissions\AdmissionNotifier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class AdmissionApplicationController extends Controller
{
    public function __construct(
        private AdmissionNotifier $notifier,
        private AdmissionMigrationService $migrator,
    ) {}

    /* ==================================================================
     | Listing
     ================================================================== */
    public function index()
    {
        return view('admissions.applications.index', [
            'counts' => AdmissionApplication::query()->selectRaw('status, COUNT(*) as c')->groupBy('status')->pluck('c', 'status'),
            'levels' => EducationLevel::orderBy('sequence')->get(['id', 'name']),
            'years'  => AdmissionApplication::whereNotNull('academic_year')->distinct()->orderByDesc('academic_year')->pluck('academic_year'),
            'config' => ['dataUrl' => route('admissions.data')],
        ]);
    }

    public function data(Request $request)
    {
        $request->validate([
            'status'             => ['nullable', Rule::in(array_keys(AdmissionApplication::STATUS_META))],
            'education_level_id' => ['nullable', 'string', 'max:12'],
            'academic_year'      => ['nullable', 'string', 'max:9'],
        ]);

        $search = trim((string) data_get($request->input('search'), 'value'));

        $query = AdmissionApplication::query()
            ->with(['gradeLevel', 'guardians'])
            ->when($request->filled('status'), fn (Builder $q) => $q->where('status', $request->input('status')))
            ->when($request->filled('education_level_id'), fn (Builder $q) => $q->where('education_level_id', $request->input('education_level_id')))
            ->when($request->filled('academic_year'), fn (Builder $q) => $q->where('academic_year', $request->input('academic_year')))
            ->when($search !== '', function (Builder $q) use ($search) {
                $like = '%'.$search.'%';
                $q->where(function (Builder $w) use ($like) {
                    $w->where('reference', 'like', $like)
                        ->orWhere('first_name', 'like', $like)
                        ->orWhere('last_name', 'like', $like)
                        ->orWhere('contact_name', 'like', $like)
                        ->orWhere('contact_phone', 'like', $like)
                        ->orWhere('contact_email', 'like', $like);
                });
            });

        $recordsTotal    = AdmissionApplication::count();
        $recordsFiltered = (clone $query)->count();

        // Column index (as declared in the JS) => DB column
        $orderMap = [0 => 'reference', 1 => 'first_name', 3 => 'academic_year', 4 => 'submitted_at', 5 => 'status'];
        $orderCol = $orderMap[(int) $request->input('order.0.column', 4)] ?? 'submitted_at';
        $orderDir = $request->input('order.0.dir') === 'asc' ? 'asc' : 'desc';

        $rows = $query->orderBy($orderCol, $orderDir)
            ->offset(max((int) $request->input('start', 0), 0))
            ->limit(min(max((int) $request->input('length', 25), 1), 100))
            ->get();

        $data = $rows->map(function (AdmissionApplication $a) {
            $guardian = $a->primaryGuardian();

            return [
                'reference'    => $a->reference,
                'student'      => $a->first_name ? $a->full_name : null,
                'contact'      => $guardian?->full_name ?? $a->contact_name,
                'phone'        => $guardian?->phone ?? $a->contact_phone,
                'grade'        => $a->gradeLevel?->name,
                'year'         => $a->academic_year,
                'submitted'    => $a->submitted_at?->format('d M Y, H:i'),
                'status'       => $a->status,
                'status_label' => $a->statusLabel(),
                'status_color' => $a->statusColor(),
                'url'          => route('admissions.show', $a->id),
            ];
        });

        return response()->json([
            'draw'            => (int) $request->input('draw'),
            'recordsTotal'    => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data'            => $data,
        ]);
    }

    /* ==================================================================
     | One application
     ================================================================== */
    public function show(AdmissionApplication $application)
    {
        $application->load(['guardians', 'gradeLevel.educationLevel', 'reviewer', 'migratedUser', 'events.user']);

        $requirements = AdmissionRequirement::active()
            ->where('education_level_id', $application->education_level_id)
            ->orderBy('sort_order')->orderBy('created_at')->get();

        $answers = $application->answers()->with('requirement')->get()->keyBy('admission_requirement_id');

        $streams = $application->grade_level_id
            ? Stream::where('grade_level_id', $application->grade_level_id)->where('status', 'active')->orderBy('name')->get()
            : collect();

        return view('admissions.applications.show', compact('application', 'requirements', 'answers', 'streams'));
    }

    public function file(AdmissionApplication $application, AdmissionAnswer $answer)
    {
        abort_unless($answer->admission_application_id === $application->id && $answer->hasFile(), 404);

        return Storage::disk(AdmissionAnswer::DISK)->download($answer->file_path, $answer->file_name);
    }

    /* ==================================================================
     | Decisions
     ================================================================== */
    public function approve(Request $request, AdmissionApplication $application)
    {
        $data = $request->validate(['note' => ['nullable', 'string', 'max:1000']]);

        if ($application->status !== 'submitted') {
            return $this->fail('Only submitted applications can be approved.');
        }

        $requiresInterview = AdmissionLevelSetting::forLevel($application->education_level_id)->requires_interview;

        $application->forceFill([
            'status'             => 'approved',
            'interview_required' => $requiresInterview,
            'reviewed_by'        => $request->user()->id,
            'reviewed_at'        => now(),
            'decision_note'      => $data['note'] ?? null,
        ])->save();

        $message = $requiresInterview
            ? "The application for {$application->full_name} has been approved. An interview is required — the date and venue will follow shortly."
            : "The application for {$application->full_name} has been approved. The school will be in touch about the next steps.";

        $this->publish($application, 'approved', $message, $request);

        return $this->ok($requiresInterview ? 'Approved. Book the interview next.' : 'Approved. You can now migrate the applicant to a student.');
    }

    public function requestChanges(Request $request, AdmissionApplication $application)
    {
        $data = $request->validate(['note' => ['required', 'string', 'max:1000']]);

        if ($application->status !== 'submitted') {
            return $this->fail('Changes can only be requested on submitted applications.');
        }

        $application->forceFill([
            'status'        => 'changes_requested',
            'reviewed_by'   => $request->user()->id,
            'reviewed_at'   => now(),
            'decision_note' => $data['note'],
        ])->save();

        $this->publish(
            $application,
            'changes_requested',
            "The school has asked for changes to the application for {$application->full_name}: {$data['note']} Use your continuation code to update it.",
            $request
        );

        return $this->ok('Changes requested. The applicant has been notified.');
    }

    public function reject(Request $request, AdmissionApplication $application)
    {
        $data = $request->validate(['note' => ['required', 'string', 'max:1000']]);

        if (in_array($application->status, ['draft', 'admitted', 'rejected'], true)) {
            return $this->fail('This application cannot be rejected in its current status.');
        }

        $application->forceFill([
            'status'        => 'rejected',
            'reviewed_by'   => $request->user()->id,
            'reviewed_at'   => now(),
            'decision_note' => $data['note'],
        ])->save();

        $this->publish(
            $application,
            'rejected',
            "Thank you for applying. Unfortunately the application for {$application->full_name} was not successful. {$data['note']}",
            $request
        );

        return $this->ok('Application rejected. The applicant has been notified.');
    }

    /* ==================================================================
     | Interview
     ================================================================== */
    public function bookInterview(Request $request, AdmissionApplication $application)
    {
        $data = $request->validate([
            'interview_at'    => ['required', 'date', 'after:now'],
            'interview_venue' => ['required', 'string', 'max:150'],
            'interview_notes' => ['nullable', 'string', 'max:1000'],
        ], ['interview_at.after' => 'Pick a date and time in the future.']);

        if (! $application->canBookInterview()) {
            return $this->fail('An interview cannot be booked for this application.');
        }

        $when = \Illuminate\Support\Carbon::parse($data['interview_at']);

        $application->forceFill($data + [
                'status'                => 'interview_scheduled',
                'interview_result_note' => null,
            ])->save();

        $this->publish(
            $application,
            'interview_scheduled',
            "An interview for {$application->full_name} has been scheduled on {$when->format('l, d M Y')} at {$when->format('H:i')}, {$data['interview_venue']}."
            .($data['interview_notes'] ? " {$data['interview_notes']}" : ''),
            $request
        );

        return $this->ok('Interview booked. The applicant has been notified.');
    }

    public function interviewResult(Request $request, AdmissionApplication $application)
    {
        $data = $request->validate([
            'result' => ['required', Rule::in(['passed', 'failed'])],
            'note'   => ['nullable', 'string', 'max:1000'],
        ]);

        if ($application->status !== 'interview_scheduled') {
            return $this->fail('Only a scheduled interview can be marked with a result.');
        }

        $passed = $data['result'] === 'passed';

        $application->forceFill([
            'status'                => $passed ? 'interview_passed' : 'interview_failed',
            'interview_result_note' => $data['note'] ?? null,
        ])->save();

        $this->publish(
            $application,
            $passed ? 'interview_passed' : 'interview_failed',
            $passed
                ? "Good news — {$application->full_name} passed the interview. The school will finalise the admission."
                : "Thank you for attending the interview for {$application->full_name}. The school will contact you about the outcome.",
            $request
        );

        return $this->ok($passed ? 'Interview marked as passed. You can now migrate the applicant.' : 'Interview marked as not passed.');
    }

    /* ==================================================================
     | Migrate to student
     ================================================================== */
    public function migrate(Request $request, AdmissionApplication $application)
    {
        $data = $request->validate([
            'stream_id'   => ['required', 'string', Rule::exists('streams', 'id')
                ->where('grade_level_id', $application->grade_level_id)->where('status', 'active')],
            'enrolled_on' => ['nullable', 'date'],
        ], ['stream_id.required' => 'Choose the class (stream) the student will join.']);

        try {
            $student = $this->migrator->migrate($application, $data['stream_id'], $data['enrolled_on'] ?? null, $request->user());
        } catch (\DomainException $e) {
            return $this->fail($e->getMessage());
        }

        $this->notifier->notify(
            $application->refresh(),
            'Admission confirmed',
            "Congratulations! {$application->full_name} has been admitted. Admission number: {$student->userID}."
        );

        return $this->ok("Migrated successfully. Admission number {$student->userID}.");
    }

    /* ==================================================================
     | Helpers
     ================================================================== */
    /** Timeline entry (visible to the applicant) + SMS/email. */
    private function publish(AdmissionApplication $application, string $type, string $message, Request $request): void
    {
        AdmissionEvent::record($application, $type, $message, true, $request->user()->id);

        $this->notifier->notify($application, $application->statusLabel().' — '.config('app.name'), $message);
    }

    private function ok(string $message)
    {
        return back()->with('adm_success', $message);
    }

    private function fail(string $message)
    {
        return back()->with('adm_error', $message);
    }
}
