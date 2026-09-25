<form method="POST" action="{{ route('apply.save-step', 4) }}" enctype="multipart/form-data" novalidate>
    @csrf

    <h4 class="fw-bold mb-1">Documents &amp; requirements</h4>
    <p class="text-muted mb-4">
        What the school asks for
        @if ($application->educationLevel) at <strong>{{ $application->educationLevel->name }}</strong> level @endif.
    </p>

    @if ($levelSetting->instructions)
        <div class="alert alert-info d-flex gap-2"><i class="bi bi-info-circle mt-1"></i><div class="small" style="white-space: pre-line;">{{ $levelSetting->instructions }}</div></div>
    @endif

    @forelse ($requirements as $req)
        @php
            $key     = "req.{$req->id}";
            $name    = "req[{$req->id}]";
            $answer  = $answers->get($req->id);
            $current = old($key, $answer?->value);
            $invalid = $errors->has($key) ? ' is-invalid' : '';
            $inputId = 'r_'.$req->id;
        @endphp

        <div class="border rounded-4 p-3 p-md-4 mb-3" style="border-color: var(--app-border) !important;">
            <div class="d-flex align-items-start justify-content-between gap-2">
                <label class="form-label mb-1" for="{{ $inputId }}">{{ $req->label }}</label>
                <span class="badge {{ $req->is_required ? 'text-bg-danger-subtle text-danger-emphasis' : 'text-bg-light border text-muted' }}">
                    {{ $req->is_required ? 'Required' : 'Optional' }}
                </span>
            </div>
            @if ($req->help_text)
                <div class="text-muted small mb-2">{{ $req->help_text }}</div>
            @endif

            @switch($req->type)
                @case('file')
                    @if ($answer?->hasFile())
                        <div class="alert alert-success py-2 small d-flex align-items-center justify-content-between gap-2 mb-2">
                            <span><i class="bi bi-check-circle me-1"></i>Uploaded: <strong>{{ $answer->file_name }}</strong></span>
                            <a href="{{ route('apply.file', $answer->id) }}" target="_blank" class="text-decoration-none">View</a>
                        </div>
                    @endif
                    <input id="{{ $inputId }}" type="file" name="{{ $name }}" accept="{{ $req->acceptAttribute() }}" class="form-control{{ $invalid }}">
                    <div class="form-text">
                        {{ strtoupper(implode(', ', $req->extensions())) }} &middot; up to {{ rtrim(rtrim(number_format($req->max_size_kb / 1024, 1), '0'), '.') }} MB
                        @if ($answer?->hasFile()) &middot; choose a file only if you want to replace the one above @endif
                    </div>
                    @break

                @case('textarea')
                    <textarea id="{{ $inputId }}" name="{{ $name }}" rows="3" class="form-control{{ $invalid }}">{{ $current }}</textarea>
                    @break

                @case('select')
                    <select id="{{ $inputId }}" name="{{ $name }}" class="form-select{{ $invalid }}">
                        <option value="">Select…</option>
                        @foreach ($req->options ?? [] as $option)
                            <option value="{{ $option }}" @selected((string) $current === (string) $option)>{{ $option }}</option>
                        @endforeach
                    </select>
                    @break

                @case('checkbox')
                    <div class="form-check">
                        <input id="{{ $inputId }}" type="checkbox" name="{{ $name }}" value="1" class="form-check-input{{ $invalid }}" @checked((string) $current === '1')>
                        <label class="form-check-label" for="{{ $inputId }}">Yes, I confirm</label>
                    </div>
                    @break

                @default
                    <input id="{{ $inputId }}" type="{{ in_array($req->type, ['number', 'date'], true) ? $req->type : 'text' }}"
                           name="{{ $name }}" value="{{ $current }}" @if ($req->type === 'number') step="any" @endif class="form-control{{ $invalid }}">
            @endswitch

            @error($key)
            <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>
    @empty
        <div class="text-center py-5 text-muted">
            <i class="bi bi-check2-circle fs-1 text-success"></i>
            <div class="fw-semibold mt-2 text-body">Nothing extra is needed for this level</div>
            <div class="small">Continue to review your application.</div>
        </div>
    @endforelse

    @include('admissions.public.steps._nav', ['step' => 4])
</form>
