<nav class="app-navbar navbar navbar-expand navbar-light">
    <div class="d-flex align-items-center">
        <button class="btn app-sidebar-toggle d-lg-none" type="button"
                data-bs-toggle="offcanvas" data-bs-target="#appSidebar"
                aria-controls="appSidebar" aria-label="Toggle menu">
            <i class="bi bi-list fs-3"></i>
        </button>
        <button class="btn app-sidebar-collapse-toggle d-none d-lg-inline-flex"
                type="button" id="sidebarCollapseToggle" aria-label="Collapse menu">
            <i class="bi bi-list fs-3"></i>
        </button>

        <a class="navbar-brand d-flex align-items-center gap-2 ms-2"
           href="{{ Route::has('dashboard') ? route('dashboard') : '/' }}">
            @if($appSettings->get('logo_path'))
                <img src="{{ route('file', ['path' => $appSettings->get('logo_path')]) }}"
                     alt="{{ $appSettings->get('school_name') }}" class="navbar-logo">
            @else
                <span class="navbar-logo-placeholder"><i class="bi bi-mortarboard"></i></span>
            @endif
            <span class="d-none d-sm-inline navbar-school-name">{{ $appSettings->get('school_name', config('app.name')) }}</span>
        </a>
    </div>

    <div class="ms-auto d-flex align-items-center gap-2">
        <div class="dropdown">
            <button class="btn app-user-menu dropdown-toggle d-flex align-items-center gap-2"
                    type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <span class="app-user-avatar">{{ strtoupper(substr(auth()->user()->first_name ?? 'U', 0, 1)) }}</span>
                <span class="d-none d-md-inline">{{ auth()->user()->last_name ?? 'User' }}</span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item" href="{{ route('profile.show') }}"><i class="bi bi-person me-2"></i>My Profile</a></li>
                <li><a class="dropdown-item" href="{{ route('password.change.show') }}"><i class="bi bi-key me-2"></i>Change Password</a></li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <form method="POST" action="{{ Route::has('logout') ? route('logout') : '#' }}">
                        @csrf
                        <button type="submit" class="dropdown-item text-danger"><i class="bi bi-box-arrow-right me-2"></i>Logout</button>
                    </form>
                </li>
            </ul>
        </div>
    </div>
</nav>
