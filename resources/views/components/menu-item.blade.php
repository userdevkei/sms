@php
    $user = auth()->user();
    $hasChildren = !empty($item['children']);

    $canSeeSelf = empty($item['permission']) || ($user && $user->hasPermission($item['permission']));

    $visibleChildren = $hasChildren
        ? collect($item['children'])->filter(fn ($child) =>
            empty($child['permission']) || ($user && $user->hasPermission($child['permission']))
          )
        : collect();

    $isVisible = $hasChildren ? ($canSeeSelf || $visibleChildren->isNotEmpty()) : $canSeeSelf;

    $routeName = $item['route'] ?? null;
    $routeExists = $routeName && \Illuminate\Support\Facades\Route::has($routeName);

    $isActive = $hasChildren
        ? $visibleChildren->contains(fn ($child) =>
            !empty($child['route']) && \Illuminate\Support\Facades\Route::has($child['route']) && request()->routeIs($child['route'])
          )
        : ($routeExists && request()->routeIs($routeName));

    $submenuId = $hasChildren ? 'submenu-' . \Illuminate\Support\Str::slug($item['label']) : null;
@endphp

@if($isVisible)
    <li class="app-menu-item {{ $isActive ? 'active' : '' }}">
        @if($hasChildren)
            <a href="#{{ $submenuId }}" class="app-menu-link app-menu-toggle {{ $isActive ? '' : 'collapsed' }}"
               data-bs-toggle="collapse" role="button"
               aria-expanded="{{ $isActive ? 'true' : 'false' }}" aria-controls="{{ $submenuId }}">
                @if(!empty($item['icon']))<i class="bi {{ $item['icon'] }}"></i>@endif
                <span class="app-menu-label">{{ $item['label'] }}</span>
                <i class="bi bi-chevron-down app-menu-caret"></i>
            </a>
            <div class="collapse {{ $isActive ? 'show' : '' }}" id="{{ $submenuId }}">
                <ul class="app-submenu list-unstyled">
                    @foreach($visibleChildren as $child)
                        <x-menu-item :item="$child" />
                    @endforeach
                </ul>
            </div>
        @else
            <a href="{{ $routeExists ? route($routeName) : '#' }}"
               class="app-menu-link {{ $isActive ? 'active' : '' }} {{ !$routeExists ? 'disabled' : '' }}">
                @if(!empty($item['icon']))<i class="bi {{ $item['icon'] }}"></i>@endif
                <span class="app-menu-label">{{ $item['label'] }}</span>
                @if(!$routeExists)<span class="badge bg-secondary-subtle text-secondary ms-auto app-menu-soon">Soon</span>@endif
            </a>
        @endif
    </li>
@endif
