@extends('layouts.app')
@section('title', 'Vehicle/Driver Assignments')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <div>
            <h1 class="h4 mb-1">Vehicle/Driver Assignments</h1>
            <p class="text-muted mb-0">Which vehicle and driver are running each route.</p>
        </div>
        @can('transport.manage')
            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addAssignmentModal">
                <i class="bi bi-plus-lg me-1"></i> New Assignment
            </button>
        @endcan
    </div>

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <div class="row g-2 align-items-end">
                <div class="col-6 col-md-3">
                    <label class="form-label small text-muted mb-1">Status</label>
                    <select id="filterStatus" class="form-select form-select-sm">
                        <option value="">All Statuses</option>
                        <option value="active">Active</option>
                        <option value="ended">Ended</option>
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
                <table id="assignmentsTable" class="table table-hover align-middle w-100">
                    <thead>
                    <tr>
                        <th>#</th>
                        <th>Route</th>
                        <th>Vehicle</th>
                        <th>Driver</th>
                        <th>Term</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    @can('transport.manage')
        <div class="modal fade" id="addAssignmentModal" tabindex="-1" aria-labelledby="addAssignmentModalLabel">
            <div class="modal-dialog">
                <form method="POST" action="{{ route('transport.route-assignments.store') }}">
                    @csrf
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="addAssignmentModalLabel">New Assignment</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            @if($errors->any())
                                <div class="alert alert-danger small">
                                    @foreach($errors->all() as $error)
                                        <div>{{ $error }}</div>
                                    @endforeach
                                </div>
                            @endif

                            <div class="mb-2">
                                <label class="form-label">Route <span class="text-danger">*</span></label>
                                <select name="route_id" class="form-select select2-field" required>
                                    <option value="">Select route</option>
                                    @foreach($routes as $route)
                                        <option value="{{ $route->id }}">{{ $route->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-2">
                                <label class="form-label">Vehicle <span class="text-danger">*</span></label>
                                <select name="vehicle_id" class="form-select select2-field" required>
                                    <option value="">Select vehicle</option>
                                    @foreach($vehicles as $vehicle)
                                        <option value="{{ $vehicle->id }}">{{ $vehicle->registration_number }} ({{ $vehicle->capacity }} seats)</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-2">
                                <label class="form-label">Driver <span class="text-danger">*</span></label>
                                <select name="driver_id" class="form-select select2-field" required>
                                    <option value="">Select driver</option>
                                    @foreach($drivers as $driver)
                                        <option value="{{ $driver->user_id }}">{{ $driver->full_name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-2">
                                <label class="form-label">Term</label>
                                <input type="text" name="term" class="form-control" placeholder="e.g. Term 2, 2026">
                            </div>

                            <div class="row g-2">
                                <div class="col-6">
                                    <label class="form-label">Start Date</label>
                                    <input type="date" name="start_date" class="form-control">
                                </div>
                                <div class="col-6">
                                    <label class="form-label">End Date</label>
                                    <input type="date" name="end_date" class="form-control">
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-sm btn-primary">Create Assignment</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    @endcan
@endsection

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0/dist/css/select2.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">
@endpush

@push('scripts')
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0/dist/js/select2.min.js"></script>
    <script>
        const assignmentsDataUrl = @json(route('transport.route-assignments.data'));
        const canManageTransport = @json(auth()->user()->hasPermission('transport.manage'));
    </script>
    <script src="{{ asset('js/route-assignments-index.js') }}"></script>
@endpush
