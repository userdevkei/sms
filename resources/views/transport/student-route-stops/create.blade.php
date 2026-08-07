@extends('layouts.app')
@section('title', 'Assign Students to Route Stop')

@section('content')
    <div class="mb-3">
        <h1 class="h4 mb-1">Assign Students to Route Stop</h1>
        <p class="text-muted mb-0">Select one or more students to assign to the same route stop.</p>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('finance.transport.student-route-stops.store') }}">
                @csrf
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">Student(s) <span class="text-danger">*</span></label>
                        <select name="user_ids[]" class="form-select select2-field @error('user_ids') is-invalid @enderror" multiple required>
                            @foreach($students as $student)
                                <option value="{{ $student->id }}" @selected(in_array($student->id, old('user_ids', [])))>
                                    {{ $student->full_name }} ({{ $student->userID ?: '—' }})
                                </option>
                            @endforeach
                        </select>
                        @error('user_ids')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        @foreach($errors->get('user_ids.*') as $messages)
                            @foreach($messages as $message)
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @endforeach
                        @endforeach
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Route Stop <span class="text-danger">*</span></label>
                        <select name="route_stop_id" class="form-select select2-field" required>
                            <option value="">Select stop</option>
                            @foreach($routeStops as $stop)
                                <option value="{{ $stop->id }}">{{ $stop->route->name ?? '' }} — {{ $stop->name }} (KES {{ number_format($stop->fare, 2) }})</option>
                            @endforeach
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
                </div>
                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a href="{{ route('finance.transport.student-route-stops.index') }}" class="btn btn-sm btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-sm btn-primary px-4">Assign</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')<script>$('.select2-field').select2({theme:'bootstrap-5', width:'100%', placeholder: 'Select student(s)'});</script>@endpush
