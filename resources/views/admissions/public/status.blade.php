@extends('admissions.public.layout')

@section('title', 'Application status')

@section('content')
    @php
        $color     = $application->statusColor();
        $dash      = fn ($v) => filled($v) ? $v : '—';
        $showVenue = $application->interview_at && in_array($application->status, ['interview_scheduled'], true);
    @endphp

    @if (session('submitted'))
        <div class="alert alert-success d-flex gap-2"><i class="bi bi-check-circle-fill mt-1"></i>
            <div><strong>Application submitted.</strong> Keep your continuation code safe &mdash; you'll need it to check progress.</div>
        </div>
    @endif

    <div class="app-card p-4 p-md-5 mb-4 text-center">
        <div class="mx-auto mb-3 d-grid text-{{ $color }}" style="width:72px;height:72px;border-radius:20px;place-items:center;background:var(--bs-{{ $color }}-bg-subtle);">
            <i class="bi {{ $application->statusIcon() }}" style="font-size:2rem;"></i>
        </div>
        <div class="text-muted small">Application {{ $application->reference }}</div>
        <h2 class="fw-bold mt-1 mb-2">{{ $application->statusLabel() }}</h2>
        <p class="text-muted mx-auto mb-0" style="max-width: 520px;">{{ $application->statusMessage() }}</p>

        @if ($application->status === 'admitted' && $application->migratedUser)
            <div class="mt-3"><span class="badge text-bg-success fs-6">Admission number: {{ $application->migratedUser->userID }}</span></div>
        @endif

        @if ($application->status === 'rejected' && $application->decision_note)
            <div class="alert alert-light border mt-4 mb-0 small text-start">{{ $application->decision_note }}</div>
        @endif
    </div>

    @if ($showVenue)
        <div class="app-card p-4 mb-4 border-info-subtle" style="background: rgba(13,202,240,.05);">
            <div class="d-flex align-items-center gap-2 mb-3"><i class="bi bi-calendar-event text-info fs-4"></i><h5 class="fw-bold mb-0">Your interview</h5></div>
            <div class="row g-3">
                <div class="col-md-4"><div class="text-muted small">Date</div><div class="fw-semibold">{{ $application->interview_at->format('l, d M Y') }}</div></div>
                <div class="col-md-4"><div class="text-muted small">Time</div><div class="fw-semibold">{{ $application->interview_at->format('H:i') }}</div></div>
                <div class="col-md-4"><div class="text-muted small">Venue</div><div class="fw-semibold">{{ $dash($application->interview_venue) }}</div></div>
                @if ($application->interview_notes)
                    <div class="col-12"><div class="text-muted small">What to bring / notes</div><div>{{ $application->interview_notes }}</div></div>
                @endif
            </div>
        </div>
    @endif

    <div class="row g-4">
        <div class="col-lg-5">
            <div class="app-card p-4 h-100">
                <h6 class="fw-bold mb-3">Progress</h6>
                <div class="position-relative ps-4">
                    @foreach ($events as $event)
                        <div class="position-relative pb-3">
                            <span class="position-absolute rounded-circle bg-{{ $loop->first ? 'primary' : 'secondary-subtle' }}" style="left:-1.45rem;top:.3rem;width:10px;height:10px;"></span>
                            @if (! $loop->last)
                                <span class="position-absolute bg-secondary-subtle" style="left:-1.05rem;top:1rem;bottom:0;width:2px;"></span>
                            @endif
                            <div class="small">{{ $event->message }}</div>
                            <div class="text-muted" style="font-size:.75rem;">{{ $event->created_at->format('d M Y, H:i') }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="app-card p-4 h-100">
                <h6 class="fw-bold mb-3">Application summary</h6>
                <dl class="row small mb-0">
                    <dt class="col-sm-4 text-muted">Student</dt><dd class="col-sm-8">{{ $dash($application->full_name) }}</dd>
                    <dt class="col-sm-4 text-muted">Applying for</dt><dd class="col-sm-8">{{ $dash($application->gradeLevel?->name) }} &middot; {{ $dash($application->academic_year) }}</dd>
                    <dt class="col-sm-4 text-muted">Date of birth</dt><dd class="col-sm-8">{{ $dash($application->date_of_birth?->format('d M Y')) }}</dd>
                    @foreach ($application->guardians as $g)
                        <dt class="col-sm-4 text-muted">{{ $g->is_primary ? 'Parent / guardian' : ($g->is_emergency ? 'Emergency contact' : 'Second guardian') }}</dt>
                        <dd class="col-sm-8">{{ $g->full_name }} <span class="text-muted">&middot; {{ $dash($g->phone) }}</span></dd>
                    @endforeach
                    <dt class="col-sm-4 text-muted">Submitted</dt><dd class="col-sm-8">{{ $dash($application->submitted_at?->format('d M Y, H:i')) }}</dd>
                </dl>

                @php $files = $application->answers->filter(fn ($a) => $a->hasFile()); @endphp
                @if ($files->isNotEmpty())
                    <div class="app-section-title mt-4">Documents you uploaded</div>
                    @foreach ($files as $answer)
                        <div class="d-flex justify-content-between align-items-center small py-1">
                            <span><i class="bi bi-file-earmark-check text-success me-1"></i>{{ $answer->requirement?->label }}</span>
                            <a href="{{ route('apply.file', $answer->id) }}" target="_blank" class="text-decoration-none">View</a>
                        </div>
                    @endforeach
                @endif
            </div>
        </div>
    </div>

    <div class="text-center mt-4">
        <form method="POST" action="{{ route('apply.exit') }}" class="d-inline">@csrf
            <button class="btn btn-outline-secondary"><i class="bi bi-box-arrow-left me-1"></i>Done &mdash; leave this page</button>
        </form>
    </div>
@endsection
