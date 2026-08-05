{{-- resources/views/finance/exemptions/create.blade.php --}}
@extends('layouts.app')
@section('title', 'Request Exemption')

@section('content')
    <div class="mb-3"><h1 class="h4 mb-1">Request Exemption / Scholarship</h1></div>

    <div class="card border-0 shadow-sm" style="max-width: 640px;">
        <div class="card-body">
            <form method="POST" action="{{ route('finance.exemptions.store') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label">Student <span class="text-danger">*</span></label>
                    <select name="user_id" class="form-select select2-field @error('user_id') is-invalid @enderror" required>
                        <option value="">Select student</option>
                        @foreach($students as $student)<option value="{{ $student->id }}">{{ $student->full_name }} ({{ $student->userID ?: '\u2014' }})</option>@endforeach
                    </select>
                    @error('user_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">Applies To</label>
                    <select name="votehead_id" class="form-select select2-field">
                        <option value="">Whole invoice</option>
                        @foreach($voteheads as $votehead)<option value="{{ $votehead->id }}">{{ $votehead->name }}</option>@endforeach
                    </select>
                    <div class="form-text">Leave as "Whole invoice" to discount the total, or pick one votehead to discount just that line.</div>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Type <span class="text-danger">*</span></label>
                        <select name="type" class="form-select" required><option value="percentage">Percentage</option><option value="fixed">Fixed Amount (KES)</option></select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Value <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="value" class="form-control @error('value') is-invalid @enderror" value="{{ old('value') }}" required>
                        @error('value')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Academic Year <span class="text-danger">*</span></label>
                        <input type="text" name="academic_year" class="form-control" value="{{ old('academic_year', date('Y')) }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Term <span class="text-danger">*</span></label>
                        <select name="term" class="form-select" required><option value="1">Term 1</option><option value="2">Term 2</option><option value="3">Term 3</option></select>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Reason <span class="text-danger">*</span></label>
                    <textarea name="reason" rows="3" class="form-control @error('reason') is-invalid @enderror" required>{{ old('reason') }}</textarea>
                    @error('reason')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('finance.exemptions.index') }}" class="btn btn-sm btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-sm btn-warning px-4">Submit for Approval</button>
                </div>
            </form>
        </div>
    </div>
@endsection
@push('scripts')<script>$('.select2-field').select2({theme:'bootstrap-5', width:'100%'});</script>@endpush
