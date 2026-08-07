{{-- resources/views/profile/partials/student.blade.php --}}
@php
    $roomAllocations = \App\Models\RoomAllocation::where('user_id', $user->id)
        ->with('room.hostel')->latest('allocated_on')->get();

    $routeStops = \App\Models\StudentRouteStop::where('user_id', $user->id)
        ->with('routeStop.route')->latest('created_at')->get();

    $currentRoom = $roomAllocations->firstWhere('status', 'active');
    $currentRoute = $routeStops->firstWhere('status', 'active');
@endphp

<div class="kv-card kv-panel mt-4">
    <div class="kv-panel-head">
        <span class="kv-panel-icon"><i class="bi bi-building"></i></span>
        <h3>Accommodation</h3>
    </div>
    <div class="kv-panel-body">
        @if($currentRoom)
            <div class="kv-row">
                <span class="kv-label"><i class="bi bi-door-open"></i> Current Room</span>
                <span class="kv-value">{{ $currentRoom->room->full_name ?? '—' }} — {{ $currentRoom->room->hostel->name ?? '—' }}</span>
            </div>
        @else
            <p class="text-muted small mb-0">No current accommodation allocation.</p>
        @endif
    </div>
</div>

<div class="kv-card kv-panel mt-4">
    <div class="kv-panel-head">
        <span class="kv-panel-icon"><i class="bi bi-bus-front"></i></span>
        <h3>Bus Route</h3>
    </div>
    <div class="kv-panel-body">
        @if($currentRoute)
            <div class="kv-row">
                <span class="kv-label"><i class="bi bi-signpost"></i> Current Route</span>
                <span class="kv-value">{{ $currentRoute->routeStop->route->name ?? '—' }} — {{ $currentRoute->routeStop->name ?? '—' }}</span>
            </div>
        @else
            <p class="text-muted small mb-0">No current route assignment.</p>
        @endif
    </div>
</div>

<div class="kv-card kv-panel mt-4">
    <div class="kv-panel-head">
        <span class="kv-panel-icon"><i class="bi bi-clock-history"></i></span>
        <h3>Accommodation History</h3>
    </div>
    <div class="kv-panel-body">
        @if($roomAllocations->isEmpty())
            <p class="text-muted small mb-0">No accommodation history on record.</p>
        @else
            <div class="table-responsive">
                <table id="roomHistoryTable" class="table table-sm align-middle w-100">
                    <thead><tr><th>Academic Year</th><th>Hostel</th><th>Room</th><th>Allocated On</th><th>Status</th></tr></thead>
                    <tbody>
                    @foreach($roomAllocations as $ra)
                        <tr>
                            <td>{{ $ra->academic_year }}</td>
                            <td>{{ $ra->room->hostel->name ?? '—' }}</td>
                            <td>{{ $ra->room->full_name ?? '—' }}</td>
                            <td>{{ $ra->allocated_on?->format('d M Y') ?? '—' }}</td>
                            <td>
                                <span class="badge bg-{{ $ra->status === 'active' ? 'success' : 'secondary' }}-subtle text-{{ $ra->status === 'active' ? 'success' : 'secondary' }} text-capitalize">{{ $ra->status }}</span>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

<div class="kv-card kv-panel mt-4">
    <div class="kv-panel-head">
        <span class="kv-panel-icon"><i class="bi bi-clock-history"></i></span>
        <h3>Bus Route History</h3>
    </div>
    <div class="kv-panel-body">
        @if($routeStops->isEmpty())
            <p class="text-muted small mb-0">No route assignment history on record.</p>
        @else
            <div class="table-responsive">
                <table id="routeHistoryTable" class="table table-sm align-middle w-100">
                    <thead><tr><th>Term</th><th>Route</th><th>Stop</th><th class="text-end">Fare</th><th>Status</th></tr></thead>
                    <tbody>
                    @foreach($routeStops as $rs)
                        <tr>
                            <td>Term {{ $rs->term }}, {{ $rs->academic_year }}</td>
                            <td>{{ $rs->routeStop->route->name ?? '—' }}</td>
                            <td>{{ $rs->routeStop->name ?? '—' }}</td>
                            <td class="text-end">{{ number_format($rs->routeStop->fare ?? 0, 2) }}</td>
                            <td>
                                <span class="badge bg-{{ $rs->status === 'active' ? 'success' : 'secondary' }}-subtle text-{{ $rs->status === 'active' ? 'success' : 'secondary' }} text-capitalize">{{ $rs->status }}</span>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

@push('styles')<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">@endpush
@push('scripts')
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
    <script>
        if (document.getElementById('roomHistoryTable')) $('#roomHistoryTable').DataTable({ order: [[3, 'desc']], pageLength: 10 });
        if (document.getElementById('routeHistoryTable')) $('#routeHistoryTable').DataTable({ order: [[0, 'desc']], pageLength: 10 });
    </script>
@endpush
