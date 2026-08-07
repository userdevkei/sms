@extends('layouts.app')
@section('title', 'Assign Subject Teacher')

@section('content')
    <div class="mb-3"><h1 class="h4 mb-1">Assign Subject Teacher</h1></div>

    <div class="card border-0 shadow-sm" style="max-width: 640px;">
        <div class="card-body">
            <form method="POST" action="{{ route('results.assignments.store') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label">Teacher <span class="text-danger">*</span></label>
                    <select name="user_id" class="form-select select2-field @error('user_id') is-invalid @enderror" required>
                        <option value="">Select teacher</option>
                        @foreach($teachers as $teacher)
                            <option value="{{ $teacher->id }}">{{ trim($teacher->first_name . ' ' . ($teacher->middle_name ? $teacher->middle_name . ' ' : '') . $teacher->last_name) }}</option>
                        @endforeach
                    </select>
                    @error('user_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">Subject <span class="text-danger">*</span></label>
                    <select name="learning_area_id" class="form-select select2-field @error('learning_area_id') is-invalid @enderror" required>
                        <option value="">Select subject</option>
                        @foreach($learningAreas as $area)
                            <option value="{{ $area->id }}">{{ $area->name }}</option>
                        @endforeach
                    </select>
                    @error('learning_area_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">Class (Stream) <span class="text-danger">*</span></label>
                    <select name="stream_id" class="form-select select2-field @error('stream_id') is-invalid @enderror" required>
                        <option value="">Select class</option>
                        @foreach($streams as $stream)
                            <option value="{{ $stream->id }}">{{ $stream->full_name }}</option>
                        @endforeach
                    </select>
                    @error('stream_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">Academic Year <span class="text-danger">*</span></label>
                    <input type="text" name="academic_year" class="form-control @error('academic_year') is-invalid @enderror" value="{{ old('academic_year', date('Y')) }}" required>
                    @error('academic_year')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select"><option value="active" selected>Active</option><option value="inactive">Inactive</option></select>
                </div>
                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('results.assignments.index') }}" class="btn btn-sm btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-sm btn-primary px-4">Save Assignment</button>
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
    <script>$('.select2-field').select2({theme:'bootstrap-5', width:'100%'});</script>
@endpush
