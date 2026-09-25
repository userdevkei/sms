@extends('admissions.public.layout')

@section('title', 'Apply for admission')

@section('content')
    @php
        $startBag    = $errors->getBag('start');
        $continueBag = $errors->getBag('continue');
        $resendBag   = $errors->getBag('resend');
        $showResend  = $resendBag->any() || session('resend_sent');
    @endphp

    <div class="text-center mb-4 pt-2">
        <h1 class="fw-bold mb-2" style="font-size: clamp(1.6rem, 4vw, 2.2rem);">Apply for admission to {{ setting('school_name') }}</h1>
        <p class="text-muted mx-auto mb-0" style="max-width: 560px;">
            No account needed. Start an application and we'll send you a code &mdash; use it any time to finish your application or to check its status.
        </p>
    </div>

    @if ($resume)
        <div class="app-card p-3 p-md-4 mb-4 d-flex flex-wrap align-items-center justify-content-between gap-3 border-primary-subtle" style="background: rgba(var(--bs-primary-rgb), .04);">
            <div>
                <div class="fw-semibold">Welcome back</div>
                <div class="text-muted small">
                    Application <strong>{{ $resume->reference }}</strong>
                    @if ($resume->first_name) for {{ $resume->full_name }} @endif
                    &middot; {{ $resume->statusLabel() }}
                </div>
            </div>
            <div class="d-flex gap-2">
                <form method="POST" action="{{ route('apply.exit') }}">@csrf<button class="btn btn-outline-secondary btn-sm">Not you?</button></form>
                <a class="btn btn-sm btn-primary" href="{{ $resume->isEditable() ? route('apply.wizard') : route('apply.status') }}">
                    {{ $resume->isEditable() ? 'Continue application' : 'View status' }} <i class="bi bi-arrow-right ms-1"></i>
                </a>
            </div>
        </div>
    @endif

    <div class="row g-4">
        {{-- Start new --}}
        <div class="col-lg-6">
            <div class="app-card p-4 h-100">
                <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="badge rounded-pill text-bg-primary">New</span>
                    <h4 class="fw-bold mb-0">Start an application</h4>
                </div>
                <p class="text-muted small mb-4">Tell us how to reach you. We'll send your continuation code there.</p>

                <form method="POST" action="{{ route('apply.start') }}" novalidate>
                    @csrf
                    <div class="mb-3">
                        <label class="form-label" for="contact_name">Your name <span class="text-danger">*</span></label>
                        <input id="contact_name" name="contact_name" value="{{ old('contact_name') }}" autocomplete="name"
                               class="form-control {{ $startBag->has('contact_name') ? 'is-invalid' : '' }}" placeholder="Parent or guardian's full name">
                        <div class="invalid-feedback">{{ $startBag->first('contact_name') }}</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="contact_phone">Phone number</label>
                        <input id="contact_phone" name="contact_phone" value="{{ old('contact_phone') }}" inputmode="tel" autocomplete="tel"
                               class="form-control {{ $startBag->has('contact_phone') ? 'is-invalid' : '' }}" placeholder="0712 345 678">
                        <div class="invalid-feedback">{{ $startBag->first('contact_phone') }}</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="contact_email">Email <span class="text-muted fw-normal">(optional if you gave a phone)</span></label>
                        <input id="contact_email" type="email" name="contact_email" value="{{ old('contact_email') }}" autocomplete="email"
                               class="form-control {{ $startBag->has('contact_email') ? 'is-invalid' : '' }}" placeholder="you@example.com">
                        <div class="invalid-feedback">{{ $startBag->first('contact_email') }}</div>
                    </div>
                    <button class="btn btn-primary w-100 btn-md">Start application <i class="bi bi-arrow-right ms-1"></i></button>
                </form>
            </div>
        </div>

        {{-- Continue --}}
        <div class="col-lg-6">
            <div class="app-card p-4 h-100">
                <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="badge rounded-pill text-bg-secondary">Returning</span>
                    <h4 class="fw-bold mb-0">Continue or check status</h4>
                </div>
                <p class="text-muted small mb-4">Enter the code we sent you by SMS or email.</p>

                <form method="POST" action="{{ route('apply.continue') }}" novalidate>
                    @csrf
                    <div class="mb-3">
                        <label class="form-label" for="code">Continuation code</label>
                        <input id="code" name="code" value="{{ old('code') }}" autocomplete="off" autocapitalize="characters" spellcheck="false"
                               class="form-control form-control-lg app-code text-center {{ $continueBag->has('code') ? 'is-invalid' : '' }}" placeholder="K7Q3Z-9PXLM">
                        <div class="invalid-feedback">{{ $continueBag->first('code') }}</div>
                    </div>
                    <button class="btn btn-dark w-100 btn-md">Continue <i class="bi bi-arrow-right ms-1"></i></button>
                </form>

                <div class="mt-4 pt-3 border-top">
                    <a class="small text-decoration-none" data-bs-toggle="collapse" href="#lostCode" role="button" aria-expanded="{{ $showResend ? 'true' : 'false' }}">
                        <i class="bi bi-question-circle me-1"></i>I lost my code
                    </a>

                    <div class="collapse {{ $showResend ? 'show' : '' }} mt-3" id="lostCode">
                        @if (session('resend_sent'))
                            <div class="alert alert-success small py-2 mb-3">
                                If we found an application with those details, a new code is on its way. The old code no longer works.
                            </div>
                        @endif
                        <form method="POST" action="{{ route('apply.resend') }}" class="row g-2" novalidate>
                            @csrf
                            <div class="col-12">
                                <label class="form-label small" for="contact">Phone or email you applied with</label>
                                <input id="contact" name="contact" value="{{ old('contact') }}" class="form-control {{ $resendBag->has('contact') ? 'is-invalid' : '' }}" placeholder="0712 345 678 or you@example.com">
                                <div class="invalid-feedback">{{ $resendBag->first('contact') }}</div>
                            </div>
                            <div class="col-12"><button class="btn btn-outline-primary btn-sm">Send me a new code</button></div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- How it works --}}
    <div class="row g-3 mt-2 text-center">
        @foreach ([
            ['bi-person-vcard', 'Fill in details', 'Student, parent and grade level — a few minutes per step.'],
            ['bi-cloud-arrow-up', 'Upload documents', 'Only what the school asks for, for the level you choose.'],
            ['bi-send-check', 'Submit', 'Review everything, then send it to the school.'],
            ['bi-bell', 'Get updates', 'Approval and interview details arrive by SMS / email.'],
        ] as [$icon, $title, $text])
            <div class="col-6 col-md-3">
                <div class="p-2">
                    <i class="bi {{ $icon }} fs-3 text-primary"></i>
                    <div class="fw-semibold mt-2">{{ $title }}</div>
                    <div class="text-muted small">{{ $text }}</div>
                </div>
            </div>
        @endforeach
    </div>

    @if ($levels->isNotEmpty())
        <div class="text-center mt-4 text-muted small">
            Now admitting:
            @foreach ($levels as $level)
                <span class="badge rounded-pill text-bg-light border">{{ $level->name }}</span>
            @endforeach
        </div>
    @endif
@endsection
