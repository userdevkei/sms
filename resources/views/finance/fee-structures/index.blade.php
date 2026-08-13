@extends('layouts.app')
@section('title', 'Fee Structures')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <div>
            <h1 class="h4 mb-1">Fee Structures</h1>
            <p class="text-muted mb-0">Versioned fee schedules per grade level and term.</p>
        </div>
        @can('fee_structures.manage')
            <a href="{{ route('finance.fee-structures.create') }}" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg me-1"></i> New Fee Structure</a>
        @endcan
    </div>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-6 col-md-4">
                    <label class="form-label small text-muted mb-1">Grade Level</label>
                    <select name="grade_level" class="form-select form-select-sm select2" onchange="this.form.submit()">
                        <option value="">All Grade Levels</option>
                        @foreach($gradeLevels as $grade)
                            <option value="{{ $grade->id }}" @selected(request('grade_level') === $grade->id)>{{ $grade->name }}</option>
                        @endforeach
                    </select>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 w-100" id="feeStructuresTable">
                    <thead><tr><th>#</th><th>Grade Level</th><th>Version</th><th>Total</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
                    <tbody>
                    @foreach($feeStructures as $fs)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td class="fw-semibold">{{ $fs->gradeLevel->name }}</td>
                            <td data-order="{{ $fs->version }}">v{{ $fs->version }}</td>
                            <td data-order="{{ $fs->items->sum('amount') }}">KES {{ number_format($fs->items->sum('amount'), 2) }}</td>
                            <td>
                                @php $map = ['draft' => 'warning', 'published' => 'success', 'archived' => 'secondary']; @endphp
                                <span class="badge bg-{{ $map[$fs->status] }}-subtle text-{{ $map[$fs->status] }} text-capitalize">{{ $fs->status }}</span>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('finance.fee-structures.show', $fs->id) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a>
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
        $(function () {
            $('#feeStructuresTable').DataTable({
                order: [[1, 'asc'], [2, 'desc']], // Grade Level asc, then newest version first
                columnDefs: [
                    { orderable: false, targets: [5] }, // Actions column
                    { className: 'text-end', targets: [5] },
                ],
                language: {
                    search: '',
                    searchPlaceholder: 'Search fee structures...',
                    emptyTable: 'No fee structures found.',
                },
                pageLength: 25,
                lengthMenu: [10, 25, 50, 100],
            });
        });
    </script>
@endpush
