@extends('admissions.public.layout')

@section('title', 'Your application')

@section('content')
    @php
        $stepFiles = [1 => 'student', 2 => 'guardians', 3 => 'grade', 4 => 'documents', 5 => 'review'];

        $maskedPhone = $application->contact_phone ? '+'.substr($application->contact_phone, 0, 5).'••••'.substr($application->contact_phone, -2) : null;
        $maskedEmail = $application->contact_email ? preg_replace('/(?<=.{2}).(?=[^@]*@)/', '•', $application->contact_email) : null;
        $sentTo      = collect([$maskedPhone, $maskedEmail])->filter()->implode(' and ');
    @endphp

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <div class="text-muted small">Application</div>
            <div class="fw-bold">{{ $application->reference }}
                <span class="badge text-bg-{{ $application->statusColor() }} ms-1">{{ $application->statusLabel() }}</span>
            </div>
        </div>
        <form method="POST" action="{{ route('apply.exit') }}">@csrf
            <button class="btn btn-link text-muted text-decoration-none btn-sm"><i class="bi bi-box-arrow-left me-1"></i>Leave (progress is saved)</button>
        </form>
    </div>

    @if ($application->status === 'changes_requested' && $application->decision_note)
        <div class="alert alert-warning d-flex gap-2">
            <i class="bi bi-exclamation-circle mt-1"></i>
            <div><strong>The school asked for changes:</strong> {{ $application->decision_note }}<br>
                <span class="small">Update what's needed, then go to the last step and submit again.</span></div>
        </div>
    @endif

    @if ($errors->has('general'))
        <div class="alert alert-danger d-flex gap-2"><i class="bi bi-x-circle mt-1"></i><div>{{ $errors->first('general') }}</div></div>
    @endif

    {{-- Stepper --}}
    <div class="stepper">
        @foreach ($steps as $n => $label)
            @php $state = $n === $step ? 'current' : ($n < $maxStep ? 'done' : ''); @endphp

            @if ($n <= $maxStep)
                <a class="s {{ $state }}" href="{{ route('apply.wizard', ['step' => $n]) }}">
                    <div class="dot">@if ($state === 'done')<i class="bi bi-check-lg"></i>@else{{ $n }}@endif</div>
                    <div class="lbl">{{ $label }}</div>
                </a>
            @else
                <div class="s">
                    <div class="dot">{{ $n }}</div>
                    <div class="lbl">{{ $label }}</div>
                </div>
            @endif
        @endforeach
    </div>

    <div class="app-card p-4 p-md-5">
        @include('admissions.public.steps.'.$stepFiles[$step])
    </div>

    {{-- Shown once, right after an application is started --}}
    @if (session('new_code'))
        <div class="modal fade" id="codeModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow">
                    <div class="modal-body text-center p-4 p-md-5">
                        <div class="mx-auto mb-3 d-grid" style="width:56px;height:56px;border-radius:16px;background:rgba(var(--bs-primary-rgb),.1);place-items:center;">
                            <i class="bi bi-key-fill fs-3 text-primary"></i>
                        </div>
                        <h4 class="fw-bold">Save your continuation code</h4>
                        <p class="text-muted">This is how you come back to your application &mdash; no account needed.</p>

                        <div class="app-code display-6 fw-bold my-3 user-select-all" id="codeText">{{ session('new_code') }}</div>

                        <button type="button" class="btn btn-outline-primary btn-sm mb-3" id="copyCode"><i class="bi bi-clipboard me-1"></i>Copy code</button>

                        @if ($sentTo)
                            <p class="small text-muted mb-4">We're also sending it to {{ $sentTo }}. Keep it private &mdash; anyone with this code can open the application.</p>
                        @endif

                        <button type="button" class="btn btn-primary btn-md w-100" data-bs-dismiss="modal">I've saved it &mdash; let's start</button>
                    </div>
                </div>
            </div>
        </div>

        @push('scripts')
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    const el = document.getElementById('codeModal');
                    bootstrap.Modal.getOrCreateInstance(el).show();

                    document.getElementById('copyCode').addEventListener('click', function () {
                        const code = document.getElementById('codeText').textContent.trim();
                        (navigator.clipboard ? navigator.clipboard.writeText(code) : Promise.reject()).then(
                            () => { this.innerHTML = '<i class="bi bi-check2 me-1"></i>Copied'; },
                            () => { this.textContent = 'Select the code and copy it'; }
                        );
                    });
                });
            </script>
        @endpush
    @endif
@endsection
