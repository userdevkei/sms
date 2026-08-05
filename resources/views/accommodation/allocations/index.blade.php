@extends('layouts.app')
@section('title', 'Room Allocations')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <div>
            <h1 class="h4 mb-1">Room Allocations</h1>
            <p class="text-muted mb-0">Students currently housed, by room.</p>
        </div>
        @can('accommodation.manage')
            <a href="{{ route('accommodation.allocations.create') }}" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg me-1"></i> Direct Allocation</a>
        @endcan
    </div>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-6 col-md-3">
                    <label class="form-label small text-muted mb-1">Hostel</label>
                    <select name="hostel" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">All Hostels</option>
                        @foreach($hostels as $hostel)
                            <option value="{{ $hostel->id }}" @selected(request('hostel') === $hostel->id)>{{ $hostel->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label small text-muted mb-1">Status</label>
                    <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="active" @selected(request('status', 'active') === 'active')>Currently Housed</option>
                        <option value="ended" @selected(request('status') === 'ended')>Vacated</option>
                    </select>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead><tr><th>Student</th><th>Hostel</th><th>Room</th><th>Academic Year</th><th>Allocated On</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
                    <tbody>
                    @forelse($allocations as $allocation)
                        <tr>
                            <td>{{ $allocation->student->full_name }}</td>
                            <td>{{ $allocation->room->hostel->name }}</td>
                            <td>{{ $allocation->room->name }}</td>
                            <td>{{ $allocation->academic_year }} @if($allocation->term) ({{ $allocation->term }}) @endif</td>
                            <td>{{ $allocation->allocated_on?->format('d M Y') ?? '-' }}</td>
                            <td><span class="badge bg-{{ $allocation->status === 'active' ? 'success' : 'secondary' }}-subtle text-{{ $allocation->status === 'active' ? 'success' : 'secondary' }} text-capitalize">{{ $allocation->status }}</span></td>
                            <td class="text-end">
                                @can('accommodation.manage')
                                    @if($allocation->status === 'active')
                                        <form method="POST" action="{{ route('accommodation.allocations.vacate', $allocation->id) }}" class="d-inline" onsubmit="return confirm('Vacate {{ $allocation->student->full_name }} from this room?')">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-warning" title="Vacate"><i class="bi bi-box-arrow-right"></i></button>
                                        </form>
                                    @endif
                                    <button type="button" class="btn btn-sm btn-outline-danger btn-delete-allocation" data-url="{{ route('accommodation.allocations.destroy', $allocation->id) }}" title="Delete record"><i class="bi bi-trash"></i></button>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-3">No allocations found.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.querySelectorAll('.btn-delete-allocation').forEach(btn => {
            btn.addEventListener('click', function () {
                if (!confirm('Delete this allocation record permanently?')) return;
                fetch(this.dataset.url, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' } })
                    .then(r => r.json()).then(res => res.success ? location.reload() : alert(res.message));
            });
        });
    </script>
@endpush
