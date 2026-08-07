@extends('layouts.app')
@section('title', 'Flag Progression Exception')

@section('content')
    <div class="mb-3">
        <h1 class="h4 mb-1">Flag a Progression Exception</h1>
        <p class="text-muted mb-0">For {{ $gradeLevel->name }}. Use this only for genuinely special cases - a reason is required and an Admin/Principal must approve it before it takes effect.</p>
    </div>

    <div class="card border-0 shadow-sm" style="max-width: 640px;">
        <div class="card-body">
            <form method="POST" action="{{ route('curriculum.progression.exceptions.store', $gradeLevel->id) }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label">Student <span class="text-danger">*</span></label>
                    <select name="enrollment_id" class="form-select select2-field @error('enrollment_id') is-invalid @enderror" required>
                        <option value="">Select student</option>
                        @foreach($enrollments as $enrollment)
                            <option value="{{ $enrollment->id }}" @selected(old('enrollment_id') === $enrollment->id)>
                                {{ $enrollment->student->full_name }} ({{ $enrollment->student->userID ?: '-' }})
                            </option>
                        @endforeach
                    </select>
                    @error('enrollment_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Exception Type <span class="text-danger">*</span></label>
                    <select name="type" id="typeSelect" class="form-select @error('type') is-invalid @enderror" required>
                        <option value="">Select type</option>
                        <option value="repeat" @selected(old('type') === 'repeat')>Repeat Grade (e.g. extended absence, missed the whole year)</option>
                        <option value="transferred_out" @selected(old('type') === 'transferred_out')>Transferred Out</option>
                        <option value="withdrawn" @selected(old('type') === 'withdrawn')>Withdrawn</option>
                        <option value="deceased" @selected(old('type') === 'deceased')>Deceased</option>
                    </select>
                    @error('type')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3" id="repeatYearField" style="display:none;">
                    <label class="form-label">Academic Year to Repeat In <span class="text-danger">*</span></label>
                    <input type="text" name="new_academic_year" class="form-control @error('new_academic_year') is-invalid @enderror"
                           value="{{ old('new_academic_year', date('Y') + 1) }}">
                    @error('new_academic_year')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Reason <span class="text-danger">*</span></label>
                    <textarea name="reason" rows="4" class="form-control @error('reason') is-invalid @enderror"
                              placeholder="Be specific - this becomes part of the student's academic record." required>{{ old('reason') }}</textarea>
                    @error('reason')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('curriculum.progression.show', $gradeLevel->id) }}" class="btn btn-sm btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-sm btn-warning px-4">Submit for Approval</button>
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

        const typeSelect = document.getElementById('typeSelect');
        const repeatField = document.getElementById('repeatYearField');

        function toggleRepeatField() {
            repeatField.style.display = typeSelect.value === 'repeat' ? '' : 'none';
        }
        typeSelect.addEventListener('change', toggleRepeatField);
        toggleRepeatField();
    </script>
@endpush
