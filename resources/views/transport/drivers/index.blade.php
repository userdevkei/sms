@extends('layouts.app')
@section('title', 'Drivers')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <div>
            <h1 class="h4 mb-1">Drivers</h1>
            <p class="text-muted mb-0">Every user tagged with the Driver role, and their license status.</p>
        </div>
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
                        <option value="no_license">No License Details</option>
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
                <table id="driversTable" class="table table-hover table-sm table-striped fs-sm w-100">
                    <thead>
                    <tr>
                        <th></th>
                        <th>Name</th>
                        <th>License Number</th>
                        <th>License Expiry</th>
                        <th>Phone</th>
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
        const driversDataUrl = @json(route('transport.drivers.data'));
        const canManageTransport = @json(auth()->user()->hasPermission('transport.manage'));
    </script>
    <script src="{{ asset('js/drivers-index.js') }}"></script>
@endpush
