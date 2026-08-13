@extends('layouts.app')
@section('title', $hostel->name)

@section('content')
    <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
            <h1 class="h4 mb-1">{{ $hostel->name }}
                <span class="badge bg-{{ $hostel->status === 'active' ? 'success' : 'secondary' }}-subtle text-{{ $hostel->status === 'active' ? 'success' : 'secondary' }} text-capitalize">{{ $hostel->status }}</span>
            </h1>
            <p class="text-muted mb-0">{{ ucfirst($hostel->gender) }} . Warden: {{ $hostel->warden?->full_name ?? 'Unassigned' }}</p>
        </div>
        <a href="{{ route('accommodation.hostels.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Back</a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="text-uppercase text-muted small mb-0">Rooms</h6>
                @can('accommodation.manage')
                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addRoomModal">
                        <i class="bi bi-plus-lg me-1"></i> Add Room
                    </button>
                @endcan
            </div>

            <div class="table-responsive">
                <table class="table table-hover table-sm table-striped fs-sm w-100">
                    <thead><tr><th>#</th><th>Room</th><th>Capacity</th><th>Occupied</th><th>Available</th><th>Fee / Term</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
                    <tbody>
                    @forelse($hostel->rooms as $room)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td class="fw-semibold">{{ $room->name }}</td>
                            <td>{{ $room->capacity }}</td>
                            <td>{{ $room->occupied_beds }}</td>
                            <td><span class="badge bg-{{ $room->available_beds > 0 ? 'success' : 'danger' }}-subtle text-{{ $room->available_beds > 0 ? 'success' : 'danger' }}">{{ $room->available_beds }}</span></td>
                            <td>{{ $room->fee_per_term ? 'KES ' . number_format($room->fee_per_term, 0) : ($hostel->default_fee_per_term ? 'KES ' . number_format($hostel->default_fee_per_term, 0) . ' (default)' : '-') }}</td>
                            <td><span class="badge bg-{{ $room->status === 'active' ? 'success' : 'secondary' }}-subtle text-{{ $room->status === 'active' ? 'success' : 'secondary' }} text-capitalize">{{ $room->status }}</span></td>
                            <td class="text-end">
                                @can('accommodation.manage')
                                    <button type="button" class="btn btn-sm btn-outline-primary me-1 btn-edit-room"
                                            data-id="{{ $room->id }}" data-name="{{ $room->name }}" data-capacity="{{ $room->capacity }}"
                                            data-fee="{{ $room->fee_per_term }}" data-status="{{ $room->status }}"
                                            data-url="{{ route('accommodation.rooms.update', $room->id) }}"><i class="bi bi-pencil"></i></button>
                                    <button type="button" class="btn btn-sm btn-outline-danger btn-delete-room" data-url="{{ route('accommodation.rooms.destroy', $room->id) }}"><i class="bi bi-trash"></i></button>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-3">No rooms defined yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @can('accommodation.manage')
        <div class="modal fade" id="addRoomModal" tabindex="-1">
            <div class="modal-dialog">
                <form method="POST" action="{{ route('accommodation.rooms.store', $hostel->id) }}">
                    @csrf
                    <div class="modal-content">
                        <div class="modal-header"><h5 class="modal-title">Add Room</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                        <div class="modal-body">
                            @if($errors->any() && !old('_editing'))<div class="alert alert-danger small">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif
                            <div class="mb-2"><label class="form-label">Room Name <span class="text-danger">*</span></label><input type="text" name="name" class="form-control" placeholder="e.g. Room 12" required></div>
                            <div class="row g-2 mb-2">
                                <div class="col-6"><label class="form-label">Capacity (beds) <span class="text-danger">*</span></label><input type="number" name="capacity" class="form-control" min="1" required></div>
                                <div class="col-6"><label class="form-label">Fee / Term (KES)</label><input type="number" step="0.01" name="fee_per_term" class="form-control" min="0" placeholder="Uses hostel default if blank"></div>
                            </div>
                            <div class="mb-0"><label class="form-label">Status</label>
                                <select name="status" class="form-select"><option value="active" selected>Active</option><option value="inactive">Inactive</option></select>
                            </div>
                        </div>
                        <div class="modal-footer"><button type="submit" class="btn btn-sm btn-primary">Save</button></div>
                    </div>
                </form>
            </div>
        </div>

        <div class="modal fade" id="editRoomModal" tabindex="-1">
            <div class="modal-dialog">
                <form method="POST" id="editRoomForm" action="">
                    @csrf @method('PUT')
                    <div class="modal-content">
                        <div class="modal-header"><h5 class="modal-title">Edit Room</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                        <div class="modal-body">
                            <div class="mb-2"><label class="form-label">Room Name <span class="text-danger">*</span></label><input type="text" name="name" id="edit_room_name" class="form-control" required></div>
                            <div class="row g-2 mb-2">
                                <div class="col-6"><label class="form-label">Capacity <span class="text-danger">*</span></label><input type="number" name="capacity" id="edit_room_capacity" class="form-control" min="1" required></div>
                                <div class="col-6"><label class="form-label">Fee / Term (KES)</label><input type="number" step="0.01" name="fee_per_term" id="edit_room_fee" class="form-control" min="0"></div>
                            </div>
                            <div class="mb-0"><label class="form-label">Status</label>
                                <select name="status" id="edit_room_status" class="form-select"><option value="active">Active</option><option value="inactive">Inactive</option></select>
                            </div>
                        </div>
                        <div class="modal-footer"><button type="submit" class="btn btn-sm btn-primary">Update</button></div>
                    </div>
                </form>
            </div>
        </div>
    @endcan
@endsection

@push('scripts')
    <script>
        document.querySelectorAll('.btn-edit-room').forEach(btn => {
            btn.addEventListener('click', function () {
                document.getElementById('editRoomForm').action = this.dataset.url;
                document.getElementById('edit_room_name').value = this.dataset.name;
                document.getElementById('edit_room_capacity').value = this.dataset.capacity;
                document.getElementById('edit_room_fee').value = this.dataset.fee || '';
                document.getElementById('edit_room_status').value = this.dataset.status;
                new bootstrap.Modal(document.getElementById('editRoomModal')).show();
            });
        });

        document.querySelectorAll('.btn-delete-room').forEach(btn => {
            btn.addEventListener('click', function () {
                if (!confirm('Delete this room?')) return;
                fetch(this.dataset.url, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' } })
                    .then(r => r.json()).then(res => res.success ? location.reload() : alert(res.message));
            });
        });
    </script>
@endpush
