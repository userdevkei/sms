@extends('layouts.app')
@section('title', 'Users')

@section('content')

    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <div>
            <h1 class="h4 mb-1">System Users</h1>
            <p class="text-muted mb-0">Manage everyone with access to the system.</p>
        </div>
        <div class="d-flex gap-2">
            <div class="dropdown">
                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    <i class="bi bi-download me-1"></i> Export
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item export-link" target="_blank" href="#" data-format="pdf"><i class="bi bi-file-earmark-pdf text-danger me-2"></i>Export as PDF</a></li>
                    <li><a class="dropdown-item export-link" href="#" data-format="excel"><i class="bi bi-file-earmark-excel text-success me-2"></i>Export as Excel</a></li>
                </ul>
            </div>
            @can('users.manage')
                <a href="{{ route('users.create') }}" class="btn btn-sm btn-primary">
                    <i class="bi bi-plus-lg me-1"></i> Add User
                </a>
            @endcan
        </div>
    </div>
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <div class="row g-2 align-items-end">
                <div class="col-6 col-md-3">
                    <label class="form-label small text-muted mb-1">Status</label>
                    <select id="filterStatus" class="form-select form-select-sm">
                        <option value="">All Statuses</option>
                        <option value="pending">Pending</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="suspended">Suspended</option>
                        <option value="transferred">Transferred</option>
                        <option value="graduated">Graduated</option>
                        <option value="deceased">Deceased</option>
                    </select>
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label small text-muted mb-1">Gender</label>
                    <select id="filterGender" class="form-select form-select-sm">
                        <option value="">All</option>
                        <option value="male">Male</option>
                        <option value="female">Female</option>
                    </select>
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label small text-muted mb-1">Role</label>
                    <select id="filterRole" class="form-select form-select-sm">
                        <option value="">All Roles</option>
                        @foreach($roles as $role)
                            <option value="{{ $role->id }}">{{ $role->name }}</option>
                        @endforeach
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
                <table id="usersTable" class="table table-hover align-middle w-100">
                    <thead>
                    <tr>
                        <th>#</th>
                        <th></th>
                        <th>User ID</th>
                        <th>Name</th>
                        <th>Gender</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Role(s)</th>
                        <th>Status</th>
                        <th>Joined</th>
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
        const usersDataUrl = @json(route('users.data'));
        const canManageUsers = @json(auth()->user()->hasPermission('users.manage'));
    </script>
    <script src="{{ asset('js/users-index.js') }}"></script>
@endpush
@push('scripts')
    <script>
        const exportPdfUrl = @json(route('users.export.pdf'));
        const exportExcelUrl = @json(route('users.export.excel'));
    </script>
@endpush
