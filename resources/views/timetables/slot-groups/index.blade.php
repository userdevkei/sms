@extends('layouts.app')
@section('title', 'Time Slot Groups')

@push('styles')
    <link href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css" rel="stylesheet">
@endpush

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">Time Slot Groups</h1>
        @can('timetables.manage')
            <a href="{{ route('timeslot-groups.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg me-1"></i> Add Group
            </a>
        @endcan
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table id="timeslotGroupsTable" class="table table-sm table-sm fs-sm table-striped align-middle w-100">
                    <thead>
                    <tr><th>#</th><th>Name</th><th>Code</th><th>Slots Defined</th><th>Status</th><th class="text-end">Actions</th></tr>
                    </thead>
                    <tbody>
                    @foreach($groups as $group)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $group->name }}</td>
                            <td><code>{{ $group->code }}</code></td>
                            <td>{{ $group->time_slots_count }}</td>
                            <td data-order="{{ $group->status }}">
                                <span class="badge {{ $group->status === 'active' ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }} border">
                                    {{ ucfirst($group->status) }}
                                </span>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('timeslots.index', ['group' => $group->id]) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-clock"></i> Manage Slots
                                </a>
                                @can('timetables.manage')
                                    <a href="{{ route('timeslot-groups.edit', $group->id) }}" class="btn btn-sm btn-outline-secondary">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                @endcan
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            $('#timeslotGroupsTable').DataTable({
                order: [[0, 'asc']],
                columnDefs: [
                    { orderable: false, searchable: false, targets: 4 } // Actions column
                ],
                language: {
                    emptyTable: 'No time slot groups yet. Create one for each schedule pattern (e.g. Lower Primary, Secondary).',
                    searchPlaceholder: 'Search groups...'
                }
            });
        });
    </script>
@endpush
