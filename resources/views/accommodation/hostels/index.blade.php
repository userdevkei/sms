@extends('layouts.app')
@section('title', 'Hostels')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <div>
            <h1 class="h4 mb-1">Hostels</h1>
            <p class="text-muted mb-0">Dormitories, capacity, and occupancy.</p>
        </div>
        @can('accommodation.manage')
            <a href="{{ route('accommodation.hostels.create') }}" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg me-1"></i> Add Hostel</a>
        @endcan
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover table-sm table-striped fs-sm w-100">
                    <thead><tr><th>#</th><th>Name</th><th>Gender</th><th>Warden</th><th>Rooms</th><th>Occupancy</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
                    <tbody>
                    @forelse($hostels as $hostel)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td class="fw-semibold"><a href="{{ route('accommodation.hostels.show', $hostel->id) }}">{{ $hostel->name }}</a></td>
                            <td class="text-capitalize">{{ $hostel->gender }}</td>
                            <td>{{ $hostel->warden?->full_name ?? '-' }}</td>
                            <td>{{ $hostel->rooms_count }}</td>
                            <td>
                                <span class="badge bg-{{ $hostel->total_occupied >= $hostel->total_capacity && $hostel->total_capacity > 0 ? 'danger' : 'primary' }}-subtle text-{{ $hostel->total_occupied >= $hostel->total_capacity && $hostel->total_capacity > 0 ? 'danger' : 'primary' }}">
                                    {{ $hostel->total_occupied }} / {{ $hostel->total_capacity }}
                                </span>
                            </td>
                            <td><span class="badge bg-{{ $hostel->status === 'active' ? 'success' : 'secondary' }}-subtle text-{{ $hostel->status === 'active' ? 'success' : 'secondary' }} text-capitalize">{{ $hostel->status }}</span></td>
                            <td class="text-end">
                                @can('accommodation.manage')
                                    <a href="{{ route('accommodation.hostels.edit', $hostel->id) }}" class="btn btn-sm btn-outline-primary me-1"><i class="bi bi-pencil"></i></a>
                                    <button type="button" class="btn btn-sm btn-outline-danger btn-delete-hostel" data-url="{{ route('accommodation.hostels.destroy', $hostel->id) }}"><i class="bi bi-trash"></i></button>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-3">No hostels defined yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.querySelectorAll('.btn-delete-hostel').forEach(btn => {
            btn.addEventListener('click', function () {
                if (!confirm('Delete this hostel?')) return;
                fetch(this.dataset.url, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' } })
                    .then(r => r.json()).then(res => res.success ? location.reload() : alert(res.message));
            });
        });
    </script>
@endpush
