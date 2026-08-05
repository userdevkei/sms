@extends('layouts.app')
@section('title', 'Permissions')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <div>
            <h1 class="h4 mb-1">All Permissions</h1>
            <p class="text-muted mb-0">Reference list of every permission in the system, grouped by module.</p>
        </div>
        <a href="{{ route('roles.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to Roles
        </a>
    </div>

    <div class="alert alert-info small">
        <i class="bi bi-info-circle me-1"></i>
        Permissions are defined in code and can't be created or removed here — assign them to roles from the
        <a href="{{ route('roles.index') }}">Roles</a> page.
    </div>

    @foreach($permissionsByModule as $module => $permissions)
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white border-0 pt-3">
                <h6 class="text-uppercase text-muted small mb-0">{{ str_replace('_', ' ', $module) }}</h6>
            </div>
            <div class="card-body pt-0">
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0 w-100 table-striped">
                        <thead>
                        <tr>
                            <th>Permission</th>
                            <th>Description</th>
                            <th class="text-center" style="width: 120px;">Used By</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($permissions as $permission)
                            <tr>
                                <td><code>{{ $permission->name }}</code></td>
                                <td class="text-muted">{{ $permission->description ?: '—' }}</td>
                                <td class="text-center">
                                    <span class="badge bg-secondary-subtle text-secondary">{{ $permission->roles_count }} role(s)</span>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endforeach
@endsection
