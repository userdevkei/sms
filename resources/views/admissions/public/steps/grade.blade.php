@php
    $gradeKey  = old('grade_level_id', $application->grade_level_id);
    $yearKey   = old('academic_year', $application->academic_year ?? $defaultYear);
    $sibling   = (bool) old('has_sibling_in_school', $application->has_sibling_in_school);
@endphp

<form method="POST" action="{{ route('apply.save-step', 3) }}" novalidate>
    @csrf

    <h4 class="fw-bold mb-1">Grade level application</h4>
    <p class="text-muted mb-4">Which class is the student applying for?</p>

    <div class="row g-3">
        <div class="col-md-4">
            <label class="form-label" for="academic_year">Year of entry <span class="text-danger">*</span></label>
            <select id="academic_year" name="academic_year" class="form-select @error('academic_year') is-invalid @enderror">
                @foreach ($years as $year)
                    <option value="{{ $year }}" @selected((string) $yearKey === (string) $year)>{{ $year }}</option>
                @endforeach
            </select>
            @error('academic_year')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>

        <div class="col-md-8">
            <label class="form-label" for="grade_level_id">Grade level <span class="text-danger">*</span></label>
            <select id="grade_level_id" name="grade_level_id" class="form-select @error('grade_level_id') is-invalid @enderror">
                <option value="">Select grade level…</option>
                @foreach ($gradeGroups as $group => $grades)
                    <optgroup label="{{ $group }}">
                        @foreach ($grades as $grade)
                            <option value="{{ $grade->id }}" @selected($gradeKey === $grade->id)>{{ $grade->name }}</option>
                        @endforeach
                    </optgroup>
                @endforeach
            </select>
            @error('grade_level_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            <div class="form-text">The documents we ask for next depend on the level you choose.</div>
        </div>
    </div>

    <div class="app-section-title">Previous school</div>
    <div class="row g-3">
        <x-admissions.field name="previous_school" label="Last school attended" :value="$application->previous_school" col="col-md-8" hint="Leave blank if the student has not attended school before." />
        <x-admissions.field name="previous_grade" label="Last class completed" :value="$application->previous_grade" col="col-md-4" />
        <x-admissions.field name="reason_for_leaving" label="Reason for changing schools" :value="$application->reason_for_leaving" />
    </div>

    <div class="app-section-title">Anything else we should plan for?</div>
    <div class="row g-3">
        <div class="col-md-6">
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" role="switch" id="needs_boarding" name="needs_boarding" value="1" @checked(old('needs_boarding', $application->needs_boarding))>
                <label class="form-check-label" for="needs_boarding">Boarding needed</label>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" role="switch" id="needs_transport" name="needs_transport" value="1" @checked(old('needs_transport', $application->needs_transport))>
                <label class="form-check-label" for="needs_transport">School transport needed</label>
            </div>
        </div>
        <div class="col-12">
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" role="switch" id="has_sibling_in_school" name="has_sibling_in_school" value="1" @checked($sibling)>
                <label class="form-check-label" for="has_sibling_in_school">A brother or sister already attends this school</label>
            </div>
        </div>
        <div class="col-12" id="siblingBox" style="{{ $sibling ? '' : 'display:none;' }}">
            <label class="form-label" for="sibling_details">Sibling's name and class</label>
            <input id="sibling_details" name="sibling_details" value="{{ old('sibling_details', $application->sibling_details) }}" class="form-control @error('sibling_details') is-invalid @enderror">
            @error('sibling_details')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>
    </div>

    @include('admissions.public.steps._nav', ['step' => 3])
</form>

@push('scripts')
    <script>
        document.getElementById('has_sibling_in_school').addEventListener('change', function () {
            document.getElementById('siblingBox').style.display = this.checked ? '' : 'none';
        });
    </script>
@endpush
