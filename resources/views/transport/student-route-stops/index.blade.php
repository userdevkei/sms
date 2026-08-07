@extends('layouts.app')
@section('title', 'Student Route Stops')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <div>
            <h1 class="h4 mb-1">Student Route Stops</h1>
            <p class="text-muted mb-0">Students assigned to a transport route stop for the current term.</p>
        </div>
        @can('transport.manage')
            <a href="{{ route('finance.transport.student-route-stops.create') }}" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg me-1"></i> Assign Student</a>
        @endcan
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table id="assignmentsTable" class="table table-hover align-middle w-100 table-striped">
                    <thead>
                        <tr><th>#</th>
                            <th>Student</th><th>Admission No.</th><th>Route</th><th>Stop</th>
                            <th class="text-end">Fare</th><th>Term</th><th>Status</th>
                            @can('transport.manage')<th class="text-end">Actions</th>@endcan
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($assignments as $a)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $a->student->full_name ?? '—' }}</td>
                                <td>{{ $a->student->userID ?? '—' }}</td>
                                <td>{{ $a->routeStop->route->name ?? '—' }}</td>
                                <td>{{ $a->routeStop->name ?? '—' }}</td>
                                <td class="text-end">{{ number_format($a->routeStop->fare ?? 0, 2) }}</td>
                                <td>Term {{ $a->term }}, {{ $a->academic_year }}</td>
                                <td>
                                    <span class="badge {{ $a->status === 'active' ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }} text-capitalize">{{ $a->status }}</span>
                                </td>
                                @can('transport.manage')
                                    <td class="text-end">
                                        @if($a->status === 'active')
                                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeAssignment('{{ $a->id }}')"><i class="bi bi-trash"></i></button>
                                        @endif
                                    </td>
                                @endcan
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('styles')<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">@endpush
@push('scripts')
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $('#assignmentsTable').DataTable({ order: [[0, 'asc']], pageLength: 25 });

        const deleteUrlTemplate = @json(route('finance.transport.student-route-stops.destroy', ['studentRouteStop' => '__ID__']));

        function removeAssignment(id) {
            if (! confirm('Remove this route stop assignment?')) return;

            fetch(deleteUrlTemplate.replace('__ID__', id), {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': @json(csrf_token()), 'Accept': 'application/json' }
            })
            .then(r => r.json())
            .then(data => { data.success ? location.reload() : alert(data.message); });
        }
    </script>
@endpush
