@php
    $dash = fn ($v) => filled($v) ? $v : '—';
    $requirements = $requirements ?? collect();
@endphp

<h4 class="fw-bold mb-1">Review &amp; submit</h4>
<p class="text-muted mb-4">Check everything below. You can go back to any step to make changes.</p>

@foreach ([
    1 => 'Student',
    2 => 'Parent / guardian',
    3 => 'Grade level',
] as $n => $title)
    <div class="border rounded-4 p-3 p-md-4 mb-3" style="border-color: var(--app-border) !important;">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h6 class="fw-bold mb-0">{{ $title }}</h6>
            <a href="{{ route('apply.wizard', ['step' => $n]) }}" class="small text-decoration-none"><i class="bi bi-pencil me-1"></i>Edit</a>
        </div>

        <dl class="row mb-0 small">
            @if ($n === 1)
                <dt class="col-sm-4 text-muted">Name</dt><dd class="col-sm-8">{{ $dash($application->full_name) }}</dd>
                <dt class="col-sm-4 text-muted">Gender</dt><dd class="col-sm-8">{{ $dash(ucfirst((string) $application->gender)) }}</dd>
                <dt class="col-sm-4 text-muted">Date of birth</dt><dd class="col-sm-8">{{ $dash($application->date_of_birth?->format('d M Y')) }}</dd>
                <dt class="col-sm-4 text-muted">Nationality</dt><dd class="col-sm-8">{{ $dash($application->citizenship) }}</dd>
                <dt class="col-sm-4 text-muted">Birth certificate no.</dt><dd class="col-sm-8">{{ $dash($application->birth_certificate_no) }}</dd>
                <dt class="col-sm-4 text-muted">Location</dt><dd class="col-sm-8">{{ $dash(collect([$application->home_address, $application->ward, $application->sub_county, $application->county])->filter()->implode(', ')) }}</dd>
                <dt class="col-sm-4 text-muted">Medical / allergies</dt><dd class="col-sm-8">{{ $dash($application->medical_conditions) }}</dd>
                <dt class="col-sm-4 text-muted">Special needs</dt><dd class="col-sm-8">{{ $dash($application->special_needs) }}</dd>
            @elseif ($n === 2)
                @forelse ($application->guardians as $g)
                    <dt class="col-sm-4 text-muted">{{ $g->is_primary ? 'Primary' : ($g->is_emergency ? 'Emergency contact' : 'Second') }}</dt>
                    <dd class="col-sm-8">{{ $g->full_name }} ({{ $g->relationshipLabel() }})<br><span class="text-muted">{{ $dash($g->phone) }} &middot; {{ $dash($g->email) }}</span></dd>
                @empty
                    <dd class="col-12 text-danger">No parent or guardian added yet.</dd>
                @endforelse
            @else
                <dt class="col-sm-4 text-muted">Applying for</dt><dd class="col-sm-8">{{ $dash($application->gradeLevel?->name) }} &middot; {{ $dash($application->academic_year) }}</dd>
                <dt class="col-sm-4 text-muted">Previous school</dt><dd class="col-sm-8">{{ $dash($application->previous_school) }}</dd>
                <dt class="col-sm-4 text-muted">Boarding / transport</dt>
                <dd class="col-sm-8">{{ $application->needs_boarding ? 'Boarding' : 'Day scholar' }}{{ $application->needs_transport ? ' · Transport' : '' }}</dd>
                <dt class="col-sm-4 text-muted">Sibling in school</dt><dd class="col-sm-8">{{ $application->has_sibling_in_school ? $dash($application->sibling_details) : 'No' }}</dd>
            @endif
        </dl>
    </div>
@endforeach

<div class="border rounded-4 p-3 p-md-4 mb-4" style="border-color: var(--app-border) !important;">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h6 class="fw-bold mb-0">Documents &amp; requirements</h6>
        <a href="{{ route('apply.wizard', ['step' => 4]) }}" class="small text-decoration-none"><i class="bi bi-pencil me-1"></i>Edit</a>
    </div>

    @forelse ($requirements as $req)
        @php $answer = $answers->get($req->id); $has = $req->isFile() ? $answer?->hasFile() : filled($answer?->value); @endphp
        <div class="d-flex justify-content-between align-items-center py-2 {{ $loop->last ? '' : 'border-bottom' }} small">
            <span>{{ $req->label }}</span>
            @if ($has)
                <span class="text-success"><i class="bi bi-check-circle me-1"></i>{{ \Illuminate\Support\Str::limit($answer->displayValue(), 32) }}</span>
            @elseif ($req->is_required)
                <span class="text-danger"><i class="bi bi-exclamation-circle me-1"></i>Missing</span>
            @else
                <span class="text-muted">Not provided</span>
            @endif
        </div>
    @empty
        <div class="small text-muted">No documents required for this level.</div>
    @endforelse
</div>

<form method="POST" action="{{ route('apply.submit') }}">
    @csrf

    <div class="form-check mb-3">
        <input class="form-check-input @error('declaration') is-invalid @enderror" type="checkbox" name="declaration" value="1" id="declaration" @checked(old('declaration'))>
        <label class="form-check-label" for="declaration">
            I confirm that the information given is true and complete, and I am authorised to apply on the student's behalf.
        </label>
        @error('declaration')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </div>

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
        <a href="{{ route('apply.wizard', ['step' => 4]) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Back</a>
        <button type="submit" class="btn btn-success btn-sm"><i class="bi bi-send-check me-1"></i>Submit application</button>
    </div>
</form>
