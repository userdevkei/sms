@extends('layouts.app')
@section('title', 'Learning Areas')

@push('styles')
    <link href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css" rel="stylesheet">
@endpush

@section('content')
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <div>
            <h1 class="h4 mb-1">Learning Areas (Subjects)</h1>
            <p class="text-muted mb-0">Subjects offered, and the grade levels each one applies to.</p>
        </div>
        @can('curriculum.manage')
            <a href="{{ route('curriculum.learning-areas.create') }}" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg me-1"></i> Add Learning Area</a>
        @endcan
    </div>

    <x-curriculum-tabs active="learning-areas" />

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table id="learningAreasTable" class="table table-hover table-sm table-striped fs-sm w-100">
                    <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Code</th>
                        <th>Compulsory</th>
                        <th>Grade Levels</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($learningAreas as $area)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td class="fw-semibold">{{ $area->name }}</td>
                            <td>{{ $area->code ?: '—' }}</td>
                            <td data-order="{{ $area->is_compulsory ? 1 : 0 }}">
                                {!! $area->is_compulsory ? '<span class="badge bg-primary-subtle text-primary">Compulsory</span>' : '<span class="badge bg-light text-muted">Elective</span>' !!}
                            </td>
                            <td>{{ $area->grade_levels_count }} grade(s)</td>
                            <td data-order="{{ $area->status }}">
                                <span class="badge bg-{{ $area->status === 'active' ? 'success' : 'secondary' }}-subtle text-{{ $area->status === 'active' ? 'success' : 'secondary' }} text-capitalize">{{ $area->status }}</span>
                            </td>
                            <td class="text-end">
                                @can('curriculum.manage')
                                    <a href="{{ route('curriculum.learning-areas.edit', $area->id) }}" class="btn btn-sm btn-outline-primary me-1"><i class="bi bi-pencil"></i></a>
                                    <button type="button" class="btn btn-sm btn-outline-danger btn-delete-area" data-url="{{ route('curriculum.learning-areas.destroy', $area->id) }}"><i class="bi bi-trash"></i></button>
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
            $('#learningAreasTable').DataTable({
                order: [[0, 'asc']],
                pageLength: 25,
                columnDefs: [
                    { orderable: false, searchable: false, targets: 5 } // Actions column
                ],
                language: {
                    emptyTable: 'No learning areas defined yet.',
                    searchPlaceholder: 'Search learning areas...'
                }
            });

            document.querySelectorAll('.btn-delete-area').forEach(btn => {
                btn.addEventListener('click', function () {
                    if (!confirm('Delete this learning area?')) return;
                    fetch(this.dataset.url, {
                        method: 'DELETE',
                        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' }
                    }).then(r => r.json()).then(res => res.success ? location.reload() : alert(res.message));
                });
            });
        });
    </script>
@endpush
