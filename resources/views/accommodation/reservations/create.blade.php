@extends('layouts.app')
@section('title', 'New Reservation')

@section('content')
    <div class="mb-3"><h1 class="h4 mb-1">New Boarding Reservation</h1></div>

    <div class="card border-0 shadow-sm" style="max-width: 640px;">
        <div class="card-body">
            <form method="POST" action="{{ route('accommodation.reservations.store') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label">Student <span class="text-danger">*</span></label>
                    <select name="user_id" id="studentSelect" class="form-select select2-field @error('user_id') is-invalid @enderror" required>
                        <option value="">Select student</option>
                        @foreach($students as $student)
                            <option value="{{ $student->id }}" data-gender="{{ $student->gender }}">
                                {{ $student->full_name }} ({{ $student->userID ?: '-' }})
                            </option>
                        @endforeach
                    </select>
                    @error('user_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Hostel <span class="text-danger">*</span></label>
                    <select name="hostel_id" id="hostelSelect" class="form-select select2-field @error('hostel_id') is-invalid @enderror" required>
                        <option value="">Select student first</option>
                        @foreach($hostels as $hostel)
                            <option value="{{ $hostel->id }}" data-gender="{{ $hostel->gender }}">
                                {{ $hostel->name }} ({{ ucfirst($hostel->gender) }})
                            </option>
                        @endforeach
                    </select>
                    <div class="form-text">Hostel list narrows to match the selected student's gender.</div>
                    @error('hostel_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Academic Year <span class="text-danger">*</span></label>
                        <input type="text" name="academic_year" class="form-control" value="{{ old('academic_year', date('Y')) }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Term</label>
                        <input type="text" name="term" class="form-control" placeholder="e.g. Term 1">
                    </div>
                </div>

                <div class="mb-3 mt-3">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" rows="3" class="form-control" placeholder="Any special requirements or context"></textarea>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('accommodation.reservations.index') }}" class="btn btn-sm btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-sm btn-primary px-4">Submit Reservation</button>
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
        $(function () {
            $('.select2-field').select2({ theme: 'bootstrap-5', width: '100%' });

            function filterHostelsByGender(gender) {
                const $hostel = $('#hostelSelect');
                $hostel.find('option[data-gender]').each(function () {
                    const hostelGender = $(this).data('gender');
                    const matches = !gender || hostelGender === 'mixed' || hostelGender === gender;
                    $(this).prop('disabled', !matches);
                });
                $hostel.val('').trigger('change.select2');
            }

            $('#studentSelect').on('change', function () {
                filterHostelsByGender($(this).find(':selected').data('gender'));
            });
        });
    </script>
@endpush
