@extends('layouts.app')
@section('title', 'Fleet Management')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <div>
            <h1 class="h4 mb-1">Fleet Management</h1>
            <p class="text-muted mb-0">Vehicles, capacity, and compliance tracking.</p>
        </div>
        @can('transport.manage')
            <a href="{{ route('transport.vehicles.create') }}" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg me-1"></i> Add Vehicle</a>
        @endcan
    </div>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <div class="row g-2">
                <div class="col-6 col-md-3">
                    <label class="form-label small text-muted mb-1">Status</label>
                    <select id="filterStatus" class="form-select form-select-sm">
                        <option value="">All Statuses</option>
                        <option value="active">Active</option>
                        <option value="under_maintenance">Under Maintenance</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table id="vehiclesTable" class="table table-hover table-sm table-striped fs-sm w-100">
                    <thead>
                    <tr>
                        <th>#</th><th>Registration No.</th><th>Make/Model</th><th>Capacity</th>
                        <th>Insurance Expiry</th><th>Inspection Expiry</th><th>Status</th><th class="text-end">Actions</th>
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
        const vehiclesDataUrl = @json(route('transport.vehicles.data'));
        const canManageTransport = @json(auth()->user()->hasPermission('transport.manage'));
    </script>
    <script src="{{ asset('js/vehicles-index.js') }}"></script>
@endpush
