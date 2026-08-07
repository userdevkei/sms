@extends('layouts.app')
@section('title', 'Enroll Student')

@section('content')
    <div class="mb-3">
        <h1 class="h4 mb-1">Enroll Student</h1>
        <p class="text-muted mb-0">Place {{ $student->full_name }} into a grade level and class.</p>
    </div>

    <div class="card border-0 shadow-sm" style="max-width: 640px;">
        <div class="card-body">
            <form method="POST" action="{{ route('curriculum.enrollments.store', $student->id) }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label">Grade Level <span class="text-danger">*</span></label>
                    <select name="grade_level_id" id="gradeLevelSelect" class="form-select select2-field @error('grade_level_id') is-invalid @enderror" required>
                        <option value="">Select grade level</option>
                        @foreach($gradeLevels as $grade)
                            <option value="{{ $grade->id }}" @selected(old('grade_level_id') === $grade->id)>
                                {{ $grade->name }} ({{ $grade->educationLevel->name }})
                            </option>
                        @endforeach
                    </select>
                    @error('grade_level_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Stream / Class</label>
                    <select name="stream_id" id="streamSelect" class="form-select select2-field @error('stream_id') is-invalid @enderror" disabled>
                        <option value="">Select grade level first</option>
                    </select>
                    @error('stream_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Academic Year <span class="text-danger">*</span></label>
                    <input type="text" name="academic_year" class="form-control @error('academic_year') is-invalid @enderror"
                           value="{{ old('academic_year', date('Y')) }}" required>
                    @error('academic_year')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Enrollment Date</label>
                    <input type="date" name="enrolled_on" class="form-control" value="{{ old('enrolled_on', date('Y-m-d')) }}">
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('students.profile', $student->id) }}" class="btn btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary px-4">Enroll Student</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $('.select2-field').select2({ theme: 'bootstrap-5', width: '100%' });

        const streamsUrlTemplate = @json(route('curriculum.enrollments.streams-for-grade', ['grade_level' => '__ID__']));

        $('#gradeLevelSelect').on('change', function () {
            const gradeId = this.value;
            const $stream = $('#streamSelect');

            $stream.empty().append('<option value="">Loading...</option>').prop('disabled', true).trigger('change.select2');

            if (!gradeId) {
                $stream.empty().append('<option value="">Select grade level first</option>').trigger('change.select2');
                return;
            }

            fetch(streamsUrlTemplate.replace('__ID__', gradeId))
                .then(res => res.json())
                .then(streams => {
                    $stream.empty().append('<option value="">No specific stream (unassigned)</option>');
                    streams.forEach(s => $stream.append(new Option(s.name, s.id)));
                    $stream.prop('disabled', false).trigger('change.select2');
                });
        });
    </script>
@endpush
