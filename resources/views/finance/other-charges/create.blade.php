@extends('layouts.app')
@section('title', 'Add Other Charge')

@section('content')
    <div class="mb-3">
        <h1 class="h4 mb-1">Add Other Charge</h1>
        <p class="text-muted mb-0">This charge is applied automatically the next time an invoice is generated for the students it covers.</p>
    </div>

    <form method="POST" action="{{ route('finance.other-charges.store') }}">
        @csrf
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Charge Type <span class="text-danger">*</span></label>
                        <select name="other_charge_type_id" class="form-select select2-field" required>
                            <option value="">Select type</option>
                            @foreach($types as $type)<option value="{{ $type->id }}">{{ $type->name }}</option>@endforeach
                        </select>
                    </div>
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
                    <div class="col-12">
                        <label class="form-label">Amount (KES) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="amount" class="form-control" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Description</label>
                        <textarea name="description" rows="2" class="form-control"></textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <label class="form-label d-block mb-2">Applies To <span class="text-danger">*</span></label>
                <div class="btn-group mb-3" role="group">
                    <input type="radio" class="btn-check" name="scope" id="scopeStudent" value="student" autocomplete="off" checked>
                    <label class="btn btn-sm btn-outline-primary" for="scopeStudent">One Student</label>

                    <input type="radio" class="btn-check" name="scope" id="scopeStream" value="stream" autocomplete="off">
                    <label class="btn btn-sm btn-outline-primary" for="scopeStream">A Stream</label>

                    <input type="radio" class="btn-check" name="scope" id="scopeGrade" value="grade_level" autocomplete="off">
                    <label class="btn btn-sm btn-outline-primary" for="scopeGrade">A Whole Grade</label>
                </div>

                <div id="scopeStudentField">
                    <label class="form-label">Student <span class="text-danger">*</span></label>
                    <select name="user_id" class="form-select select2-field">
                        <option value="">Select student</option>
                        @foreach($students as $student)<option value="{{ $student->id }}">{{ $student->full_name }} ({{ $student->userID ?: '—' }})</option>@endforeach
                    </select>
                </div>

                <div id="scopeStreamField" class="d-none">
                    <label class="form-label">Stream <span class="text-danger">*</span></label>
                    <select name="stream_id" class="form-select select2-field">
                        <option value="">Select stream</option>
                        @foreach($streams as $stream)<option value="{{ $stream->id }}">{{ $stream->name }} — {{ $stream->gradeLevel->name ?? '' }}</option>@endforeach
                    </select>
                </div>

                <div id="scopeGradeField" class="d-none">
                    <label class="form-label">Grade Level <span class="text-danger">*</span></label>
                    <select name="grade_level_id" class="form-select select2-field">
                        <option value="">Select grade level</option>
                        @foreach($gradeLevels as $grade)<option value="{{ $grade->id }}">{{ $grade->name }}</option>@endforeach
                    </select>
                </div>
            </div>
            <div class="card-footer bg-white border-0 p-4 pt-0 d-flex justify-content-end gap-2">
                <a href="{{ route('finance.other-charges.index') }}" class="btn btn-sm btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-sm btn-primary px-4">Save Charge</button>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        $('.select2-field').select2({ theme: 'bootstrap-5', width: '100%' });

        const fields = { student: '#scopeStudentField', stream: '#scopeStreamField', grade_level: '#scopeGradeField' };

        function toggleScope() {
            const selected = document.querySelector('input[name="scope"]:checked').value;
            Object.entries(fields).forEach(([key, sel]) => {
                const el = document.querySelector(sel);
                const isActive = key === selected;
                el.classList.toggle('d-none', !isActive);
                el.querySelector('select').required = isActive;
                if (!isActive) $(el.querySelector('select')).val(null).trigger('change');
            });
        }

        document.querySelectorAll('input[name="scope"]').forEach(r => r.addEventListener('change', toggleScope));
        toggleScope();
    </script>
@endpush
