<?php

// app/Http/Controllers/CommunicationController.php
namespace App\Http\Controllers;

use App\Http\Requests\StoreCommunicationRequest;
use App\Models\AdmissionGuardian;
use App\Models\Communication;
use App\Models\CommunicationTemplate;
use App\Models\GradeLevel;
use App\Models\User;
use App\Services\Communication\CommunicationService;

class CommunicationController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()?->hasPermission('communication.view'), 403);

        return view('communication.index', [
            'communications' => Communication::with('template', 'recipients')->latest()->get(),
        ]);
    }

    public function create()
    {
        abort_unless(auth()->user()?->hasPermission('communication.send'), 403);

        return view('communication.create', [
            'templates'   => CommunicationTemplate::where('is_active', true)->get(),
            'gradeLevels' => GradeLevel::orderBy('name')->get(),
            // Kept lightweight (id/name/label only) — full records aren't needed client-side.
            'students' => User::query()
                ->whereHas('roles', fn ($q) => $q->where('name', 'student'))
                ->with(['currentEnrollment'])
                ->orderBy('first_name')
                ->get(['id', 'first_name', 'last_name']),
            'staffMembers' => User::query()
                ->whereHas('roles', fn ($q) => $q->whereNotIn('name', ['student', 'guardian', 'parent']))
                ->orderBy('first_name')
                ->get(['id', 'first_name', 'last_name']),
            'guardians'   => AdmissionGuardian::orderBy('full_name')->get(['id', 'full_name', 'phone', 'email']),
        ]);
    }

    public function store(StoreCommunicationRequest $request, CommunicationService $service)
    {
        $validated = $request->validated();

        [$audienceType, $audienceParams] = match ($validated['audience_type']) {
            'manual' => ['manual', [
                'manual_recipients' => collect(preg_split('/[\r\n,]+/', $validated['recipients']))
                    ->map(fn ($d) => trim($d))->filter()->values()->all(),
            ]],
            'students' => ['students', match ($validated['students_mode']) {
                'grade'    => ['mode' => 'grade', 'grade_level_id' => $validated['grade_level_id']],
                'specific' => ['mode' => 'specific', 'ids' => $validated['student_ids']],
                default    => ['mode' => 'all'],
            }],
            'staff' => ['staff', $validated['staff_mode'] === 'specific'
                ? ['mode' => 'specific', 'ids' => $validated['staff_ids']]
                : ['mode' => 'all']],
            'guardians' => ['guardians', $validated['guardians_mode'] === 'specific'
                ? ['mode' => 'specific', 'ids' => $validated['guardian_ids']]
                : ['mode' => 'all']],
        };

        $service->create([
            'communication_template_id' => $validated['communication_template_id'] ?? null,
            'channel'  => $validated['channel'],
            'subject'  => $validated['subject'] ?? null,
            'body'     => $validated['body'],
            'audience_type'   => $audienceType,
            'audience_params' => $audienceParams,
            'send_at'         => $validated['send_at'] ?? null,
            'recurrence_rule' => $validated['recurrence_rule'] ?? null,
        ]);

        return redirect()->route('communication.index')->with('success', 'Message queued.');
    }
}
