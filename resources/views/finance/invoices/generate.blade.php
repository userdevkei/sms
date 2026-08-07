@extends('layouts.app')
@section('title', 'Generate Invoices')

@section('content')
    <div class="mb-3">
        <h1 class="h4 mb-1">Generate Invoices</h1>
        <p class="text-muted mb-0">Select one or more grade levels and/or individual students. You'll see a preview before anything is saved.</p>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('finance.invoices.preview') }}">
                @csrf
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label mb-0">Grade Level(s)</label>
                            <div class="form-check form-check-inline mb-0">
                                <input class="form-check-input" type="checkbox" id="selectAllGrades">
                                <label class="form-check-label small" for="selectAllGrades">Select all</label>
                            </div>
                        </div>
                        <select name="grade_level_ids[]" id="gradeLevelSelect" class="form-select select2-field" multiple>
                            @foreach($gradeLevels as $grade)
                                <option value="{{ $grade->id }}" @selected(in_array($grade->id, old('grade_level_ids', [])))>{{ $grade->name }}</option>
                            @endforeach
                        </select>
                        <div class="form-text">Includes every active student in the selected grade(s).</div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label mb-0">Individual Student(s)</label>
                            <div class="form-check form-check-inline mb-0">
                                <input class="form-check-input" type="checkbox" id="selectAllStudents">
                                <label class="form-check-label small" for="selectAllStudents">Select all</label>
                            </div>
                        </div>
                        <select name="student_ids[]" id="studentSelect" class="form-select select2-field" multiple>
                            @foreach($students as $student)
                                <option value="{{ $student->id }}" @selected(in_array($student->id, old('student_ids', [])))>
                                    {{ $student->full_name }} ({{ $student->userID ?: '—' }})
                                </option>
                            @endforeach
                        </select>
                        <div class="form-text">Added on top of any grade-level selection above.</div>
                    </div>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-md-3">
                        <label class="form-label">Academic Year <span class="text-danger">*</span></label>
                        <input type="text" name="academic_year" class="form-control" value="{{ old('academic_year', date('Y')) }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Term <span class="text-danger">*</span></label>
                        <select name="term" class="form-select" required>
                            <option value="1">Term 1</option><option value="2">Term 2</option><option value="3">Term 3</option>
                        </select>
                    </div>
                </div>

                @error('scope')<div class="text-danger small mb-3">{{ $message }}</div>@enderror

                <button type="submit" class="btn btn-sm btn-primary">
                    <i class="bi bi-eye me-1"></i> Preview Invoices
                </button>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $('.select2-field').select2({ theme: 'bootstrap-5', width: '100%' });

        function wireSelectAll(checkboxId, selectId) {
            const checkbox = document.getElementById(checkboxId);
            const select = document.getElementById(selectId);

            checkbox.addEventListener('change', function () {
                Array.from(select.options).forEach(opt => opt.selected = this.checked);
                $(select).trigger('change'); // refresh Select2's display
            });

            // If the user manually deselects one option after "select all"
            // was checked, un-check the box so it doesn't lie about state.
            $(select).on('change', function () {
                const allSelected = Array.from(this.options).every(opt => opt.selected);
                checkbox.checked = allSelected;
            });
        }

        wireSelectAll('selectAllGrades', 'gradeLevelSelect');
        wireSelectAll('selectAllStudents', 'studentSelect');
    </script>
@endpush
