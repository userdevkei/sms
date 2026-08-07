<div class="offcanvas offcanvas-start app-sidebar" tabindex="-1" id="appSidebar" aria-labelledby="appSidebarLabel">
    <div class="offcanvas-header d-lg-none">
        <h5 class="offcanvas-title text-white" id="appSidebarLabel">{{ $appSettings->get('school_name', 'Menu') }}</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>

    <div class="app-sidebar-brand d-none d-lg-flex align-items-center gap-2">
        @if($appSettings->get('logo_path'))
            <img src="{{ route('file', ['path' => $appSettings->get('logo_path')]) }}" alt="Logo" class="app-sidebar-logo">
        @else
            <i class="bi bi-mortarboard fs-3 text-white"></i>
        @endif
        <div class="app-sidebar-brand-text">
            <div class="fw-semibold text-white">{{ $appSettings->get('school_name', config('app.name')) }}</div>
            @if($appSettings->get('tagline'))
                <div class="app-sidebar-tagline">{{ $appSettings->get('tagline') }}</div>
            @endif
        </div>
    </div>

    <div class="offcanvas-body app-sidebar-body">
        <ul class="app-menu list-unstyled">
            @foreach($menuItems as $item)
                <x-menu-item :item="$item" />
            @endforeach
        </ul>

        @if($appSettings->get('motto'))
            <div class="app-sidebar-motto">
                <i class="bi bi-quote"></i> {{ $appSettings->get('motto') }}
            </div>
        @endif
    </div>
</div>
