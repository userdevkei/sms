@extends('layouts.app')
@section('title', 'Roles & Permissions')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <div>
            <h1 class="h4 mb-1">Roles & Permissions</h1>
            <p class="text-muted mb-0">Manage what each role in the system can do.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('permissions.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-list-check me-1"></i> View All Permissions
            </a>
            @can('roles.manage')
                <a href="{{ route('roles.create') }}" class="btn btn-sm btn-primary">
                    <i class="bi bi-plus-lg me-1"></i> Add Role
                </a>
            @endcan
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table id="rolesTable" class="table table-hover align-middle w-100 fs-sm table-striped">
                    <thead>
                    <tr>
                        <th>#</th>
                        <th>Role</th>
                        <th>Description</th>
                        <th class="text-center">Permissions</th>
                        <th class="text-center">Users</th>
                        <th class="text-center">Type</th>
                        <th class="text-end">Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($roles as $role)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>
                                <div class="fw-semibold">{{ $role->name }}</div>
                                <div class="text-muted small">{{ $role->slug }}</div>
                            </td>
                            <td class="text-muted">{{ $role->description ?: '—' }}</td>
                            <td class="text-center">
                                <span class="badge bg-primary-subtle text-primary">{{ $role->permissions_count }}</span>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-secondary-subtle text-secondary">{{ $role->users_count }}</span>
                            </td>
                            <td class="text-center">
                                @if($role->is_system)
                                    <span class="badge bg-warning-subtle text-warning"><i class="bi bi-shield-lock"></i> System</span>
                                @else
                                    <span class="badge bg-light text-muted">Custom</span>
                                @endif
                            </td>
                            <td class="text-end">
                                @can('roles.manage')
                                    <a href="{{ route('roles.edit', $role->id) }}" class="btn btn-sm btn-outline-primary me-1" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    @if(!$role->is_system)
                                        <button type="button" class="btn btn-sm btn-outline-danger btn-delete-role"
                                                data-url="{{ route('roles.destroy', $role->id) }}"
                                                data-name="{{ $role->name }}"
                                                data-users="{{ $role->users_count }}" title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    @else
                                        <button type="button" class="btn btn-sm btn-outline-secondary" disabled title="System roles cannot be deleted">
                                            <i class="bi bi-lock"></i>
                                        </button>
                                    @endif
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

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
@endpush

@push('scripts')
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
    <script src="{{ asset('js/roles-index.js') }}"></script>
@endpush
