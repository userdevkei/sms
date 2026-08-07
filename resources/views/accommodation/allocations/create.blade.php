@extends('layouts.app')
@section('title', 'Direct Room Allocation')

@section('content')
    <div class="mb-3">
        <h1 class="h4 mb-1">Direct Room Allocation</h1>
        <p class="text-muted mb-0">Place a student directly into a room, bypassing the reservation queue.</p>
    </div>

    <div class="card border-0 shadow-sm" style="max-width: 640px;">
        <div class="card-body">
            <form method="POST" action="{{ route('accommodation.allocations.store') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label">Student <span class="text-danger">*</span></label>
                    <select name="user_id" id="student-select" class="form-select select2-field @error('user_id') is-invalid @enderror" required>
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
                    <label class="form-label">Room <span class="text-danger">*</span></label>
                    <select name="room_id" id="room-select" class="form-select select2-field @error('room_id') is-invalid @enderror" required>
                        <option value="">Select student first</option>
                        @foreach($hostels as $hostel)
                            <optgroup label="{{ $hostel->name }} ({{ ucfirst($hostel->gender) }})" data-gender="{{ $hostel->gender }}">
                                @foreach($hostel->rooms as $room)
                                    @php $available = $room->availableBeds(); @endphp
                                    <option value="{{ $room->id }}" data-gender="{{ $hostel->gender }}" {{ $available <= 0 ? 'disabled' : '' }}>
                                        {{ $room->name }} - {{ $available }} bed(s) free
                                    </option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                    <div class="form-text">Room list narrows to hostels matching the selected student's gender.</div>
                    @error('room_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
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
                    <div class="col-md-6">
                        <label class="form-label">Allocated On</label>
                        <input type="date" name="allocated_on" class="form-control" value="{{ old('allocated_on', date('Y-m-d')) }}">
                    </div>
                </div>

                <div class="mb-3 mt-3">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" rows="2" class="form-control"></textarea>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('accommodation.allocations.index') }}" class="btn btn-sm btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-sm btn-primary px-4">Allocate</button>
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

            function filterRoomsByGender(gender) {
                const $room = $('#room-select');
                $room.find('optgroup').each(function () {
                    const groupGender = $(this).data('gender');
                    const matches = !gender || groupGender === 'mixed' || groupGender === gender;
                    $(this).prop('disabled', !matches).toggle(matches);
                });
                $room.find('option[data-gender]').each(function () {
                    const optGender = $(this).data('gender');
                    const matches = !gender || optGender === 'mixed' || optGender === gender;
                    $(this).prop('disabled', $(this).data('disabledByBeds') || !matches);
                });
                $room.val('').trigger('change.select2');
            }

            // preserve the "no beds" disabled state so gender filtering doesn't un-disable full rooms
            $('#room-select option').each(function () {
                $(this).data('disabledByBeds', $(this).prop('disabled'));
            });

            $('#student-select').on('change', function () {
                const gender = $(this).find(':selected').data('gender');
                filterRoomsByGender(gender);
            });
        });
    </script>
@endpush
