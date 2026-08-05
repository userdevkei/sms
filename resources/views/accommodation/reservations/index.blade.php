@extends('layouts.app')
@section('title', 'Boarding Reservations')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <div>
            <h1 class="h4 mb-1">Boarding Reservations</h1>
            <p class="text-muted mb-0">Requests ahead of physical room allocation.</p>
        </div>
        @can('accommodation.manage')
            <a href="{{ route('accommodation.reservations.create') }}" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg me-1"></i> New Reservation</a>
        @endcan
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <h6 class="text-uppercase text-muted small mb-3">Pending ({{ $pending->count() }})</h6>
            @forelse($pending as $reservation)
                <div class="border rounded p-3 mb-2">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                        <div>
                            <div class="fw-semibold">{{ $reservation->student->full_name }}
                                <span class="text-muted small">({{ $reservation->student->userID ?: '-' }})</span>
                            </div>
                            <div class="text-muted small">
                                Requested: {{ $reservation->hostel->name }}
                                @if($reservation->preferredRoom) . Preference: {{ $reservation->preferredRoom->name }} @endif
                                . {{ $reservation->academic_year }} @if($reservation->term) ({{ $reservation->term }}) @endif
                            </div>
                            @if($reservation->notes)<p class="mb-0 mt-1 small">{{ $reservation->notes }}</p>@endif
                        </div>
                        @can('accommodation.manage')
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#approveModal{{ $reservation->id }}">
                                    <i class="bi bi-check-lg"></i> Approve
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#rejectModal{{ $reservation->id }}">
                                    <i class="bi bi-x-lg"></i> Reject
                                </button>
                            </div>
                        @endcan
                    </div>
                </div>

                @can('accommodation.manage')
                    <div class="modal fade" id="approveModal{{ $reservation->id }}" tabindex="-1">
                        <div class="modal-dialog">
                            <form method="POST" action="{{ route('accommodation.reservations.approve', $reservation->id) }}">
                                @csrf
                                <div class="modal-content">
                                    <div class="modal-header"><h5 class="modal-title">Approve - Assign a Room</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                                    <div class="modal-body">
                                        <label class="form-label">Room <span class="text-danger">*</span></label>
                                        <select name="room_id" class="form-select room-select" data-hostel-id="{{ $reservation->hostel_id }}" required>
                                            <option value="">Loading rooms...</option>
                                        </select>
                                    </div>
                                    <div class="modal-footer"><button type="submit" class="btn btn-sm btn-success">Confirm Allocation</button></div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="modal fade" id="rejectModal{{ $reservation->id }}" tabindex="-1">
                        <div class="modal-dialog">
                            <form method="POST" action="{{ route('accommodation.reservations.reject', $reservation->id) }}">
                                @csrf
                                <div class="modal-content">
                                    <div class="modal-header"><h5 class="modal-title">Reject Reservation</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                                    <div class="modal-body"><label class="form-label">Notes (optional)</label><textarea name="notes" rows="3" class="form-control"></textarea></div>
                                    <div class="modal-footer"><button type="submit" class="btn btn-sm btn-danger">Confirm Rejection</button></div>
                                </div>
                            </form>
                        </div>
                    </div>
                @endcan
            @empty
                <p class="text-muted small fst-italic mb-0">No pending reservations.</p>
            @endforelse
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <h6 class="text-uppercase text-muted small mb-3">Recently Resolved</h6>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead><tr><th>Student</th><th>Hostel</th><th>Status</th><th>Reviewed By</th><th>Date</th></tr></thead>
                    <tbody>
                    @forelse($resolved as $reservation)
                        <tr>
                            <td>{{ $reservation->student->full_name }}</td>
                            <td>{{ $reservation->hostel->name }}</td>
                            <td>
                                @php $map = ['approved' => 'success', 'rejected' => 'danger', 'allocated' => 'primary', 'cancelled' => 'secondary']; @endphp
                                <span class="badge bg-{{ $map[$reservation->status] }}-subtle text-{{ $map[$reservation->status] }} text-capitalize">{{ $reservation->status }}</span>
                            </td>
                            <td>{{ $reservation->reviewedBy?->full_name ?? '-' }}</td>
                            <td>{{ $reservation->reviewed_at?->format('d M Y') ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-3">No resolved reservations yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.querySelectorAll('.room-select').forEach(select => {
            const hostelId = select.dataset.hostelId;
            fetch(`/accommodation/hostels/${hostelId}/rooms-with-space`)
                .then(res => res.json())
                .then(rooms => {
                    select.innerHTML = '<option value="">Select room</option>';
                    rooms.forEach(r => select.appendChild(new Option(r.name, r.id)));
                    if (rooms.length === 0) select.innerHTML = '<option value="">No rooms with space available</option>';
                });
        });
    </script>
@endpush
