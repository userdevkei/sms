@extends('layouts.app')
@section('title', 'Student Progression')

@section('content')
    <div class="mb-3">
        <h1 class="h4 mb-1">Student Progression</h1>
        <p class="text-muted mb-0">Under CBET, promotion is the default outcome for every active student — only genuinely special cases need an exception.</p>
    </div>

    <div class="d-flex justify-content-end mb-3">
        <a href="{{ route('curriculum.progression.exceptions.index') }}" class="btn btn-sm btn-outline-warning btn-sm">
            <i class="bi bi-flag me-1"></i> Exceptions Queue
        </a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table id="progressionTable" class="table table-hover table-sm table-striped fs-sm w-100">
                    <thead>
                    <tr>
                        <th>#</th><th>Grade Level</th><th>Education Level</th>
                        <th>Active Students</th><th>Pending Exceptions</th><th class="text-end">Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($gradeLevels as $grade)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td class="fw-semibold">{{ $grade->name }}</td>
                            <td>{{ $grade->educationLevel->name }}</td>
                            <td data-order="{{ $grade->active_student_count }}"><span class="badge bg-primary-subtle text-primary">{{ $grade->active_student_count }}</span></td>
                            <td data-order="{{ $grade->pending_exception_count }}">
                                @if($grade->pending_exception_count > 0)
                                    <span class="badge bg-warning-subtle text-warning">{{ $grade->pending_exception_count }} pending</span>
                                @else
                                    <span class="text-muted small">-</span>
                                @endif
                            </td>
                            <td class="text-end">
                                @if($grade->active_student_count > 0)
                                    <a href="{{ route('curriculum.progression.show', $grade->id) }}" class="btn btn-sm btn-primary">
                                        <i class="bi bi-arrow-up-right-circle me-1"></i> Manage Progression
                                    </a>
                                @else
                                    <span class="text-muted small fst-italic">No active students</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
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
        $('#progressionTable').DataTable({
            order: [[0, 'asc']],
            pageLength: 25,
            columnDefs: [{ targets: [0, -1], orderable: false }]
        });
    </script>
@endpush
