@extends('layouts.app')

@section('content')
@php
    $a    = $application;
    $dash = fn ($v) => filled($v) ? $v : '—';
    $file = fn ($answer) => route('admissions.file', [$a->id, $answer->id]);
@endphp

<style>
    .adm { --a-border: #e8ecf3; --a-muted: #64748b; }
    .adm .card { border: 1px solid var(--a-border); border-radius: 14px; box-shadow: 0 1px 2px rgba(15, 23, 42, .04); }
    .adm .card-header { background: transparent; border-bottom: 1px solid var(--a-border); font-weight: 700; padding: .9rem 1.25rem; }
    .adm dl { margin: 0; font-size: .9rem; }
    .adm dt { color: var(--a-muted); font-weight: 500; }
    .adm dd { margin-bottom: .55rem; }
    .adm-timeline { position: relative; padding-left: 1.4rem; }
    .adm-timeline .it { position: relative; padding-bottom: 1rem; }
    .adm-timeline .it::before { content: ''; position: absolute; left: -1.05rem; top: .95rem; bottom: -.1rem; width: 2px; background: #e5e9f2; }
    .adm-timeline .it:last-child::before { display: none; }
    .adm-timeline .it .dot { position: absolute; left: -1.4rem; top: .32rem; width: 10px; height: 10px; border-radius: 50%; background: #cbd5e1; }
    .adm-timeline .it:first-child .dot { background: var(--bs-primary); }
</style>

<div class="adm container-fluid py-4">
    <a href="{{ route('admissions.index') }}" class="text-decoration-none small text-muted"><i class="bi bi-arrow-left me-1"></i>All applications</a>

    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mt-2 mb-4">
        <div>
            <h4 class="fw-bold mb-1">{{ $a->full_name }}</h4>
            <div class="text-muted">
                <span class="font-monospace">{{ $a->reference }}</span> &middot;
                {{ $a->gradeLevel?->name ?? 'No grade chosen' }}{{ $a->academic_year ? ' · '.$a->academic_year : '' }}
            </div>
        </div>
        <span class="badge rounded-pill text-bg-{{ $a->statusColor() }} fs-6 px-3 py-2"><i class="bi {{ $a->statusIcon() }} me-1"></i>{{ $a->statusLabel() }}</span>
    </div>

    @foreach (['adm_success' => 'success', 'adm_error' => 'danger'] as $flash => $tone)
        @if (session($flash))
            <div class="alert alert-{{ $tone }} d-flex gap-2"><i class="bi bi-info-circle mt-1"></i><div>{{ session($flash) }}</div></div>
        @endif
    @endforeach
    @if ($errors->any())
        <div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-1"></i>{{ $errors->first() }}</div>
    @endif

    <div class="row g-4">
        {{-- ============ LEFT: the application ============ --}}
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header">Student</div>
                <div class="card-body">
                    <dl class="row">
                        <dt class="col-sm-4">Full name</dt><dd class="col-sm-8">{{ $dash($a->first_name ? $a->full_name : null) }}</dd>
                        <dt class="col-sm-4">Gender</dt><dd class="col-sm-8">{{ $dash(ucfirst((string) $a->gender)) }}</dd>
                        <dt class="col-sm-4">Date of birth</dt><dd class="col-sm-8">{{ $dash($a->date_of_birth?->format('d M Y')) }}{{ $a->date_of_birth ? ' ('.$a->date_of_birth->age.' yrs)' : '' }}</dd>
                        <dt class="col-sm-4">Nationality / religion</dt><dd class="col-sm-8">{{ $dash($a->citizenship) }} / {{ $dash($a->religion) }}</dd>
                        <dt class="col-sm-4">Birth certificate no.</dt><dd class="col-sm-8">{{ $dash($a->birth_certificate_no) }}</dd>
                        <dt class="col-sm-4">Location</dt><dd class="col-sm-8">{{ $dash(collect([$a->home_address, $a->ward, $a->sub_county, $a->county])->filter()->implode(', ')) }}</dd>
                        <dt class="col-sm-4">Student email</dt><dd class="col-sm-8">{{ $dash($a->student_email) }}</dd>
                        <dt class="col-sm-4">Medical / allergies</dt><dd class="col-sm-8">{{ $dash($a->medical_conditions) }}</dd>
                        <dt class="col-sm-4">Special needs</dt><dd class="col-sm-8">{{ $dash($a->special_needs) }}</dd>
                    </dl>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">Parents / guardians</div>
                <div class="card-body">
                    @forelse ($a->guardians as $g)
                        <div class="{{ $loop->last ? '' : 'pb-3 mb-3 border-bottom' }}">
                            <div class="fw-semibold">{{ $g->full_name }}
                                <span class="badge text-bg-light border ms-1">{{ $g->relationshipLabel() }}</span>
                                @if ($g->is_primary)<span class="badge text-bg-primary ms-1">Primary</span>@endif
                                @if ($g->is_emergency)<span class="badge text-bg-warning ms-1">Emergency</span>@endif
                            </div>
                            <div class="small text-muted">
                                {{ $dash($g->phone) }} &middot; {{ $dash($g->email) }} &middot; ID {{ $dash($g->id_number) }}<br>
                                {{ $dash($g->occupation) }}{{ $g->address ? ' · '.$g->address : '' }}
                            </div>
                        </div>
                    @empty
                        <div class="text-muted">None added.</div>
                    @endforelse
                    <div class="small text-muted mt-3 pt-3 border-top">Application started by <strong>{{ $a->contact_name }}</strong> &middot; {{ $dash($a->contact_phone) }} &middot; {{ $dash($a->contact_email) }}</div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">Application</div>
                <div class="card-body">
                    <dl class="row">
                        <dt class="col-sm-4">Grade / year</dt><dd class="col-sm-8">{{ $dash($a->gradeLevel?->name) }} &middot; {{ $dash($a->academic_year) }}</dd>
                        <dt class="col-sm-4">Previous school</dt><dd class="col-sm-8">{{ $dash($a->previous_school) }}{{ $a->previous_grade ? ' ('.$a->previous_grade.')' : '' }}</dd>
                        <dt class="col-sm-4">Reason for leaving</dt><dd class="col-sm-8">{{ $dash($a->reason_for_leaving) }}</dd>
                        <dt class="col-sm-4">Preferences</dt>
                        <dd class="col-sm-8">
                            <span class="badge text-bg-{{ $a->needs_boarding ? 'info' : 'light border text-muted' }}">{{ $a->needs_boarding ? 'Boarding' : 'Day scholar' }}</span>
                            @if ($a->needs_transport)<span class="badge text-bg-info">Transport</span>@endif
                        </dd>
                        <dt class="col-sm-4">Sibling in school</dt><dd class="col-sm-8">{{ $a->has_sibling_in_school ? $dash($a->sibling_details) : 'No' }}</dd>
                    </dl>
                </div>
            </div>

            <div class="card">
                <div class="card-header">Documents &amp; requirements</div>
                <div class="card-body">
                    @forelse ($requirements as $req)
                        @php $answer = $answers->get($req->id); $has = $req->isFile() ? $answer?->hasFile() : filled($answer?->value); @endphp
                        <div class="d-flex justify-content-between align-items-center gap-3 py-2 {{ $loop->last ? '' : 'border-bottom' }}">
                            <div>
                                <div class="fw-semibold small">{{ $req->label }}
                                    <span class="badge {{ $req->is_required ? 'text-bg-danger-subtle text-danger-emphasis' : 'text-bg-light border text-muted' }} ms-1">{{ $req->is_required ? 'Required' : 'Optional' }}</span>
                                </div>
                                @if (! $req->isFile() && $has)
                                    <div class="small">{{ $answer->displayValue() }}</div>
                                @endif
                            </div>
                            <div class="text-end small text-nowrap">
                                @if ($has && $req->isFile())
                                    <a href="{{ $file($answer) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-download me-1"></i>{{ \Illuminate\Support\Str::limit($answer->file_name, 24) }}</a>
                                @elseif ($has)
                                    <span class="text-success"><i class="bi bi-check-circle"></i></span>
                                @elseif ($req->is_required)
                                    <span class="text-danger"><i class="bi bi-exclamation-circle me-1"></i>Missing</span>
                                @else
                                    <span class="text-muted">Not provided</span>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="text-muted">No requirements are defined for this level.</div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- ============ RIGHT: decision, actions, timeline ============ --}}
        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header">Decision</div>
                <div class="card-body">
                    <p class="text-muted small">{{ $a->statusMessage() }}</p>

                    @if ($a->submitted_at)
                        <div class="small text-muted mb-2">Submitted {{ $a->submitted_at->format('d M Y, H:i') }}</div>
                    @endif
                    @if ($a->reviewed_at)
                        <div class="small text-muted mb-2">Reviewed by {{ $a->reviewer?->full_name ?? 'staff' }} &middot; {{ $a->reviewed_at->format('d M Y') }}</div>
                    @endif
                    @if ($a->decision_note)
                        <div class="bg-light rounded-3 p-2 small mb-3">{{ $a->decision_note }}</div>
                    @endif

                    @if ($a->interview_required)
                        <div class="small mb-3"><i class="bi bi-chat-square-text me-1"></i>An interview is <strong>required</strong> for this level.</div>
                    @endif
                    @if ($a->interview_at)
                        <div class="border rounded-3 p-2 small mb-3">
                            <div class="fw-semibold"><i class="bi bi-calendar-event me-1"></i>{{ $a->interview_at->format('D, d M Y \a\t H:i') }}</div>
                            <div class="text-muted">{{ $a->interview_venue }}</div>
                            @if ($a->interview_result_note)<div class="mt-1">Result note: {{ $a->interview_result_note }}</div>@endif
                        </div>
                    @endif

                    {{-- Status first, permission nested --}}
                    <div class="d-grid gap-2">
                        @if ($a->status === 'submitted')
                            @can('admissions.review')
                                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#approveModal"><i class="bi bi-check2-circle me-1"></i>Approve</button>
                                <button class="btn btn-outline-warning" data-bs-toggle="modal" data-bs-target="#changesModal"><i class="bi bi-arrow-repeat me-1"></i>Request changes</button>
                                <button class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#rejectModal"><i class="bi bi-x-circle me-1"></i>Reject</button>
                            @endcan
                        @elseif ($a->status === 'approved')
                            @if ($a->interview_required)
                                @can('admissions.review')
                                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#interviewModal"><i class="bi bi-calendar-plus me-1"></i>Book interview</button>
                                    <button class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#rejectModal">Reject</button>
                                @endcan
                            @else
                                @can('admissions.migrate')
                                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#migrateModal"><i class="bi bi-person-plus me-1"></i>Migrate to student</button>
                                @endcan
                                @can('admissions.review')
                                    <button class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#rejectModal">Reject</button>
                                @endcan
                            @endif
                        @elseif ($a->status === 'interview_scheduled')
                            @can('admissions.review')
                                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#resultModal"><i class="bi bi-clipboard-check me-1"></i>Record interview result</button>
                                <button class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#interviewModal">Reschedule</button>
                                <button class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#rejectModal">Reject</button>
                            @endcan
                        @elseif ($a->status === 'interview_passed')
                            @can('admissions.migrate')
                                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#migrateModal"><i class="bi bi-person-plus me-1"></i>Migrate to student</button>
                            @endcan
                        @elseif ($a->status === 'interview_failed')
                            @can('admissions.review')
                                <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#interviewModal">Book another interview</button>
                                <button class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#rejectModal">Reject</button>
                            @endcan
                        @elseif ($a->status === 'admitted')
                            @if ($a->migratedUser)
                                <a href="{{ route('students.profile', $a->migrated_user_id) }}" class="btn btn-outline-success"><i class="bi bi-mortarboard me-1"></i>Open student {{ $a->migratedUser->userID }}</a>
                            @endif
                        @elseif ($a->status === 'changes_requested')
                            <div class="small text-muted">Waiting for the applicant to update and resubmit.</div>
                        @elseif ($a->status === 'draft')
                            <div class="small text-muted">The applicant hasn't submitted yet.</div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">Timeline</div>
                <div class="card-body">
                    <div class="adm-timeline">
                        @foreach ($a->events as $event)
                            <div class="it">
                                <span class="dot"></span>
                                <div class="small">{{ $event->message }}</div>
                                <div class="text-muted" style="font-size:.75rem;">
                                    {{ $event->created_at->format('d M Y, H:i') }}{{ $event->user ? ' · '.$event->user->full_name : '' }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ================= Modals ================= --}}
<div class="modal fade" id="approveModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered"><form method="POST" action="{{ route('admissions.approve', $a->id) }}" class="modal-content">
        @csrf
        <div class="modal-header border-0 pb-0"><h5 class="modal-title fw-bold">Approve application</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <p class="text-muted small">The applicant is notified by SMS/email.
                @if (\App\Models\AdmissionLevelSetting::forLevel($a->education_level_id)->requires_interview) An interview is required for this level, so you'll book it next. @else No interview is needed — you can migrate the applicant straight away. @endif
            </p>
            <label class="form-label" for="approveNote">Note to applicant <span class="text-muted fw-normal">(optional)</span></label>
            <textarea id="approveNote" name="note" class="form-control" rows="3"></textarea>
        </div>
        <div class="modal-footer border-0 pt-0"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button class="btn btn-success">Approve</button></div>
    </form></div>
</div>

<div class="modal fade" id="changesModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered"><form method="POST" action="{{ route('admissions.request-changes', $a->id) }}" class="modal-content">
        @csrf
        <div class="modal-header border-0 pb-0"><h5 class="modal-title fw-bold">Request changes</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <p class="text-muted small">The applicant can edit and resubmit using their continuation code.</p>
            <label class="form-label" for="changesNote">What needs to change? <span class="text-danger">*</span></label>
            <textarea id="changesNote" name="note" class="form-control" rows="3" required placeholder="e.g. The birth certificate upload is unreadable — please upload a clearer copy."></textarea>
        </div>
        <div class="modal-footer border-0 pt-0"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button class="btn btn-warning">Send request</button></div>
    </form></div>
</div>

<div class="modal fade" id="rejectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered"><form method="POST" action="{{ route('admissions.reject', $a->id) }}" class="modal-content">
        @csrf
        <div class="modal-header border-0 pb-0"><h5 class="modal-title fw-bold">Reject application</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <p class="text-muted small">The applicant is notified with your reason, so keep it courteous.</p>
            <label class="form-label" for="rejectNote">Reason <span class="text-danger">*</span></label>
            <textarea id="rejectNote" name="note" class="form-control" rows="3" required></textarea>
        </div>
        <div class="modal-footer border-0 pt-0"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button class="btn btn-danger">Reject</button></div>
    </form></div>
</div>

<div class="modal fade" id="interviewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered"><form method="POST" action="{{ route('admissions.interview', $a->id) }}" class="modal-content">
        @csrf
        <div class="modal-header border-0 pb-0"><h5 class="modal-title fw-bold">{{ $a->status === 'interview_scheduled' ? 'Reschedule interview' : 'Book interview' }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <div class="mb-3">
                <label class="form-label" for="interview_at">Date &amp; time <span class="text-danger">*</span></label>
                <input id="interview_at" type="datetime-local" name="interview_at" class="form-control" min="{{ now()->format('Y-m-d\TH:i') }}"
                       value="{{ $a->interview_at && $a->interview_at->isFuture() ? $a->interview_at->format('Y-m-d\TH:i') : '' }}" required>
            </div>
            <div class="mb-3">
                <label class="form-label" for="interview_venue">Venue <span class="text-danger">*</span></label>
                <input id="interview_venue" name="interview_venue" class="form-control" value="{{ $a->interview_venue }}" placeholder="e.g. Admin block, Room 2" required>
            </div>
            <div>
                <label class="form-label" for="interview_notes">Notes for the applicant <span class="text-muted fw-normal">(optional)</span></label>
                <textarea id="interview_notes" name="interview_notes" class="form-control" rows="2" placeholder="e.g. Please bring the original birth certificate.">{{ $a->interview_notes }}</textarea>
            </div>
        </div>
        <div class="modal-footer border-0 pt-0"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary">Save &amp; notify applicant</button></div>
    </form></div>
</div>

<div class="modal fade" id="resultModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered"><form method="POST" action="{{ route('admissions.interview-result', $a->id) }}" class="modal-content">
        @csrf
        <div class="modal-header border-0 pb-0"><h5 class="modal-title fw-bold">Interview result</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <div class="btn-group w-100 mb-3" role="group">
                <input type="radio" class="btn-check" name="result" id="resPass" value="passed" required>
                <label class="btn btn-outline-success" for="resPass"><i class="bi bi-check2-circle me-1"></i>Passed</label>
                <input type="radio" class="btn-check" name="result" id="resFail" value="failed">
                <label class="btn btn-outline-danger" for="resFail"><i class="bi bi-x-circle me-1"></i>Not passed</label>
            </div>
            <label class="form-label" for="resultNote">Notes <span class="text-muted fw-normal">(internal)</span></label>
            <textarea id="resultNote" name="note" class="form-control" rows="3"></textarea>
        </div>
        <div class="modal-footer border-0 pt-0"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary">Save result</button></div>
    </form></div>
</div>

<div class="modal fade" id="migrateModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered"><form method="POST" action="{{ route('admissions.migrate', $a->id) }}" class="modal-content">
        @csrf
        <div class="modal-header border-0 pb-0"><h5 class="modal-title fw-bold">Migrate to student</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <p class="text-muted small">Creates the student account and an active enrolment for <strong>{{ $a->gradeLevel?->name }}</strong>, {{ $a->academic_year }}.</p>
            <div class="mb-3">
                <label class="form-label" for="stream_id">Class (stream) <span class="text-danger">*</span></label>
                <select id="stream_id" name="stream_id" class="form-select" required>
                    <option value="">Select stream…</option>
                    @foreach ($streams as $stream)
                        <option value="{{ $stream->id }}">{{ $stream->name }}</option>
                    @endforeach
                </select>
                @if ($streams->isEmpty())<div class="form-text text-danger">No active streams exist for this grade level yet.</div>@endif
            </div>
            <div>
                <label class="form-label" for="enrolled_on">Enrolment date</label>
                <input id="enrolled_on" type="date" name="enrolled_on" class="form-control" value="{{ now()->format('Y-m-d') }}">
            </div>
        </div>
        <div class="modal-footer border-0 pt-0"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary" @disabled($streams->isEmpty())>Migrate</button></div>
    </form></div>
</div>
@endsection
