@extends('layouts.app')
@section('title', 'Subject Teacher Assignments')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <div>
            <h1 class="h4 mb-1">Subject Teacher Assignments</h1>
            <p class="text-muted mb-0">Who teaches what, in which class - this is what scopes marks entry access.</p>
        </div>
        @can('curriculum.manage')
            <a href="{{ route('results.assignments.create') }}" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg me-1"></i> Assign Teacher</a>
        @endcan
    </div>

    <x-results-tabs active="assignments" />

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table id="assignmentsTable" class="table table-hover table-sm table-striped fs-sm w-100">
                    <thead>
                    <tr>
                        <th>#</th><th>Teacher</th><th>Subject</th><th>Class</th>
                        <th>Academic Year</th><th>Status</th><th class="text-end">Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($assignments as $a)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $a->teacher->full_name }}</td>
                            <td>{{ $a->learningArea->name }}</td>
                            <td>{{ $a->stream->full_name }}</td>
                            <td>{{ $a->academic_year }}</td>
                            <td><span class="badge bg-{{ $a->status === 'active' ? 'success' : 'secondary' }}-subtle text-{{ $a->status === 'active' ? 'success' : 'secondary' }} text-capitalize">{{ $a->status }}</span></td>
                            <td class="text-end">
                                @can('curriculum.manage')
                                    <button type="button" class="btn btn-sm btn-outline-danger btn-delete-assignment" data-url="{{ route('results.assignments.destroy', $a->id) }}"><i class="bi bi-trash"></i></button>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-3">No assignments yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
@endpush
@push('scripts')
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $('#assignmentsTable').DataTable({
            order: [[0, 'asc']],
            pageLength: 50,
            columnDefs: [{ targets: [0, -1], orderable: false }]
        });

        document.querySelectorAll('.btn-delete-assignment').forEach(btn => {
            btn.addEventListener('click', function () {
                if (!confirm('Remove this assignment?')) return;
                fetch(this.dataset.url, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' } })
                    .then(r => r.json()).then(res => res.success ? location.reload() : alert(res.message));
            });
        });
    </script>
@endpush
