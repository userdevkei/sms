@extends('layouts.app')
@section('title', 'Routes & Stops')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <div>
            <h1 class="h4 mb-1">Routes & Stops</h1>
            <p class="text-muted mb-0">Transport routes, pick-up/drop-off points, and fares.</p>
        </div>
        @can('transport.manage')
            <a href="{{ route('transport.transport-routes.create') }}" class="btn btn-sm btn-primary">
                <i class="bi bi-plus-lg me-1"></i> Add Route
            </a>
        @endcan
    </div>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <div class="row g-2 align-items-end">
                <div class="col-6 col-md-3">
                    <label class="form-label small text-muted mb-1">Status</label>
                    <select id="filterStatus" class="form-select form-select-sm">
                        <option value="">All Statuses</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <div class="col-6 col-md-3">
                    <button id="resetFilters" type="button" class="btn btn-outline-secondary btn-sm w-100">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> Reset Filters
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table id="routesTable" class="table table-hover align-middle w-100">
                    <thead>
                    <tr>
                        <th>#</th>
                        <th>Route Name</th>
                        <th>Code</th>
                        <th>Stops</th>
                        <th>Fare Range</th>
                        <th>Vehicle</th>
                        <th>Driver</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                    </thead>
                    <tbody></tbody>
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
        const routesDataUrl = @json(route('transport.transport-routes.data'));
        const canManageTransport = @json(auth()->user()->hasPermission('transport.manage'));
    </script>
    <script src="{{ asset('js/transport-routes-index.js') }}"></script>
@endpush
