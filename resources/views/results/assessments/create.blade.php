@extends('layouts.app')
@section('title', 'New Assessment')

@section('content')
    <div class="mb-3"><h1 class="h4 mb-1">New Assessment</h1></div>

    <div class="card border-0 shadow-sm" style="max-width: 640px;">
        <div class="card-body">
            <form method="POST" action="{{ route('results.assessments.store') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label">Assessment Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="e.g. Mid-Term CAT 1" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">Subject(s) <span class="text-danger">*</span></label>
                    <select name="learning_area_id[]" class="form-select select2-field @error('learning_area_id') is-invalid @enderror" multiple required>
                        @foreach($learningAreas as $area)
                            <option value="{{ $area->id }}" @selected(in_array($area->id, old('learning_area_id', [])))>{{ $area->name }}</option>
                        @endforeach
                    </select>
                    @error('learning_area_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    @error('learning_area_id.*')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Class(es) <span class="text-danger">*</span></label>
                    <select name="stream_id[]" class="form-select select2-field @error('stream_id') is-invalid @enderror" multiple required>
                        @foreach($streams as $stream)
                            <option value="{{ $stream->id }}" @selected(in_array($stream->id, old('stream_id', [])))>{{ $stream->full_name }}</option>
                        @endforeach
                    </select>
                    @error('stream_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    @error('stream_id.*')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    @if($streams->isEmpty())<div class="form-text text-danger">You have no active subject/class assignments. Ask an admin to assign you first.</div>@endif
                </div>
                <div class="mb-3">
                    <label class="form-label">Term <span class="text-danger">*</span></label>
                    <select name="academic_term_id" class="form-select select2-field @error('academic_term_id') is-invalid @enderror" required>
                        <option value="">Select term</option>
                        @foreach($academicTerms as $term)<option value="{{ $term->id }}">{{ $term->academic_year }} - Term {{ $term->term_number }}</option>@endforeach
                    </select>
                    @error('academic_term_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">Assessment Type <span class="text-danger">*</span></label>
                    <select name="assessment_type_id" id="assessmentTypeSelect" class="form-select select2-field @error('assessment_type_id') is-invalid @enderror" required>
                        <option value="">Select type</option>
                        @foreach($assessmentTypes as $type)
                            <option value="{{ $type->id }}" data-mode="{{ $type->scoring_mode }}" data-default-max="{{ $type->default_max_score }}">{{ $type->name }}</option>
                        @endforeach
                    </select>
                    @error('assessment_type_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3" id="maxScoreField">
                    <label class="form-label">Max Score</label>
                    <input type="number" name="max_score" class="form-control @error('max_score') is-invalid @enderror" value="{{ old('max_score') }}" min="1">
                    @error('max_score')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">Assessment Date</label>
                    <input type="date" name="assessment_date" class="form-control" value="{{ old('assessment_date') }}">
                </div>
                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('results.assessments.index') }}" class="btn btn-sm btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-sm btn-primary px-4">Create & Enter Marks</button>
                </div>
            </form>
        </div>
    </div>
@endsection
@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0/dist/css/select2.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">
@endpush
@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0/dist/js/select2.min.js"></script>
    <script>

        $('.select2-field').select2({ theme: 'bootstrap-5', width: '100%' });

        const typeSelect = document.getElementById('assessmentTypeSelect');
        const maxScoreField = document.getElementById('maxScoreField');

        function toggleMaxScore() {
            const opt = typeSelect.options[typeSelect.selectedIndex];
            const isScore = opt && opt.dataset.mode === 'score';
            maxScoreField.style.display = isScore ? '' : 'none';
            if (isScore && opt.dataset.defaultMax) {
                maxScoreField.querySelector('input').value = maxScoreField.querySelector('input').value || opt.dataset.defaultMax;
            }
        }
        $(typeSelect).on('change', toggleMaxScore);
        toggleMaxScore();
    </script>
@endpush
