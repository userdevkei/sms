@php
    $school = setting('school_name', config('app.name'));
    $logo   = setting('logo_path') ? route('file', ['path' => setting('logo_path')]) : null;
    $favicon   = setting('favicon_path') ?? null;
    $initials = fn ($u) => \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($u->first_name, 0, 1) . \Illuminate\Support\Str::substr($u->last_name, 0, 1));
@endphp
    <!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Guardian Portal') · {{ $school }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @if($favicon)
        <link rel="icon" href="{{ route('file', ['path' => $favicon]) }}">
    @endif
    <style>
        :root { --g-blue:#0b4a7c; --g-blue-dark:#07446b; --g-accent:#2b3aa0; --g-bg:#f4f6fa; --g-line:#e5e8ee; --g-ink:#1a1d21; --g-muted:#6b7280; }
        body { font-family:'Inter',system-ui,sans-serif; background:var(--g-bg); color:var(--g-ink); font-size:14px; }
        .g-shell { display:flex; min-height:100vh; }
        .g-sidebar { width:260px; flex:0 0 260px; background:linear-gradient(180deg,var(--g-blue),var(--g-blue-dark)); color:#fff; padding:1rem .75rem; position:sticky; top:0; height:100vh; overflow-y:auto; }
        .g-brand { display:flex; gap:.6rem; align-items:center; color:#fff; text-decoration:none; padding:.25rem .5rem 1rem; border-bottom:1px solid rgba(255,255,255,.12); margin-bottom:1rem; }
        .g-brand:hover { color:#fff; }
        .g-brand img { width:40px; height:40px; object-fit:contain; background:#fff; border-radius:.4rem; padding:2px; }
        .g-brand-name { font-weight:700; line-height:1.1; }
        .g-brand-sub { font-size:.7rem; opacity:.75; }
        .g-back { display:inline-flex; align-items:center; gap:.4rem; color:#fff; opacity:.8; text-decoration:none; font-size:.8rem; padding:0 .5rem .6rem; }
        .g-back:hover { opacity:1; color:#fff; }
        .g-child-btn { width:100%; display:flex; align-items:center; gap:.6rem; background:rgba(255,255,255,.1); border:0; color:#fff; border-radius:.6rem; padding:.55rem .65rem; text-align:left; }
        .g-avatar { width:34px; height:34px; border-radius:.5rem; background:#dbe7ff; color:var(--g-accent); display:grid; place-items:center; font-weight:700; font-size:.8rem; flex:none; }
        .g-child-meta { display:flex; flex-direction:column; line-height:1.2; min-width:0; flex:1; }
        .g-child-meta strong { font-size:.85rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .g-child-meta small { opacity:.75; font-size:.72rem; }
        .g-nav-heading { text-transform:uppercase; font-size:.65rem; letter-spacing:.08em; opacity:.6; margin:1rem .6rem .35rem; }
        .g-nav-link { display:flex; align-items:center; gap:.65rem; color:#fff; text-decoration:none; padding:.55rem .7rem; border-radius:.5rem; opacity:.92; font-weight:500; margin-bottom:2px; }
        .g-nav-link:hover { background:rgba(255,255,255,.08); color:#fff; }
        .g-nav-link.active { background:rgba(255,255,255,.16); opacity:1; }
        .g-main { flex:1; min-width:0; display:flex; flex-direction:column; }
        .g-topbar { height:60px; background:#fff; border-bottom:1px solid var(--g-line); display:flex; align-items:center; gap:.75rem; padding:0 1.25rem; position:sticky; top:0; z-index:10; }
        .g-top-brand { font-weight:700; color:var(--g-accent); font-size:1.15rem; }
        /* Full-width content area (matches admin dashboard) */
        .g-content { flex:1; padding:1.25rem 1.5rem; width:100%; }
        .g-foot { padding:.9rem 1.5rem; border-top:1px solid var(--g-line); background:#fff; color:var(--g-muted); font-size:.8rem; display:flex; justify-content:space-between; }
        .g-h1 { font-size:1.4rem; font-weight:600; margin:0; }
        .g-sub { color:var(--g-muted); margin:.15rem 0 0; }
        .g-card { background:#fff; border:1px solid var(--g-line); border-radius:.75rem; }
        .g-card-head { padding:.9rem 1.1rem; border-bottom:1px solid var(--g-line); font-weight:600; display:flex; align-items:center; gap:.5rem; }
        .g-card-body { padding:1.1rem; }
        .g-stat { padding:1rem 1.1rem; height:100%; }
        .g-stat .lbl { color:var(--g-muted); font-size:.78rem; }
        .g-stat .val { font-size:1.15rem; font-weight:600; margin-top:.2rem; }
        .g-box { border:1px solid var(--g-line); border-radius:.6rem; padding:.9rem 1rem; height:100%; }
        .g-box .lbl { color:var(--g-muted); font-size:.78rem; }
        .g-box .val { font-weight:600; margin-top:.2rem; }
        .g-pill { display:inline-flex; align-items:center; gap:.4rem; padding:.3rem .7rem; border-radius:999px; font-weight:600; font-size:.82rem; }
        .g-pill-ok { background:#e4f4ea; color:#15803d; }
        .g-pill-due { background:#fdecea; color:#b42318; }
        .g-tile { display:flex; align-items:center; gap:.7rem; padding:.85rem 1rem; background:#f6f8fb; border:1px solid var(--g-line); border-radius:.6rem; color:var(--g-ink); text-decoration:none; font-weight:500; }
        .g-tile:hover { background:#eaf0fb; color:var(--g-accent); }
        .g-tile i { font-size:1.1rem; color:var(--g-accent); }
        .g-table th { font-size:.72rem; text-transform:uppercase; letter-spacing:.04em; color:var(--g-muted); font-weight:600; white-space:nowrap; }
        .g-toggle { display:none; font-size:1.5rem; color:var(--g-ink); padding:0; }
        @media (max-width:991px) {
            .g-sidebar { position:fixed; left:-270px; z-index:30; transition:left .2s; }
            .g-shell.open .g-sidebar { left:0; }
            .g-toggle { display:inline-block; }
            .g-content { padding:1rem; }
        }
    </style>
    @stack('styles')
</head>
<body>
<div class="g-shell" id="gShell">

    <aside class="g-sidebar">
        <a class="g-brand" href="{{ route('guardian.dashboard') }}">
            @if($logo)<img src="{{ $logo }}" alt="">@endif
            <div>
                <div class="g-brand-name">{{ $school }}</div>
                <div class="g-brand-sub">{{ setting('motto') }}</div>
            </div>
        </a>

        @if($gChild)
            <a href="{{ route('guardian.dashboard') }}" class="g-back"><i class="bi bi-arrow-left"></i> All students</a>

            <div class="dropdown">
                <button type="button" class="g-child-btn" @if($gStudents->count() > 1) data-bs-toggle="dropdown" @endif>
                    <span class="g-avatar">{{ $initials($gChild) }}</span>
                    <span class="g-child-meta">
                        <strong>{{ $gChild->full_name }}</strong>
                        <small>{{ $gChild->userID }}</small>
                    </span>
                    @if($gStudents->count() > 1)<i class="bi bi-chevron-expand"></i>@endif
                </button>
                @if($gStudents->count() > 1)
                    <ul class="dropdown-menu w-100">
                        @foreach($gStudents as $s)
                            <li><a class="dropdown-item {{ $s->id === $gChild->id ? 'active' : '' }}" href="{{ $gSwitch[$s->id] }}">{{ $s->full_name }}</a></li>
                        @endforeach
                    </ul>
                @endif
            </div>
        @endif

        <nav class="mt-2">
            @foreach($gNav as $section)
                @if($section['heading'])<div class="g-nav-heading">{{ $section['heading'] }}</div>@endif
                @foreach($section['items'] as $item)
                    <a href="{{ $item['url'] }}" class="g-nav-link {{ $item['active'] ? 'active' : '' }}">
                        <i class="bi {{ $item['icon'] }}"></i><span>{{ $item['label'] }}</span>
                    </a>
                @endforeach
            @endforeach
        </nav>
    </aside>

    <div class="g-main">
        <header class="g-topbar">
            <button type="button" class="btn btn-link g-toggle" id="gToggle"><i class="bi bi-list"></i></button>
            <span class="g-top-brand">{{ $school }}</span>

            <div class="dropdown ms-auto">
                <button class="btn btn-light border rounded-pill d-flex align-items-center gap-2 py-1" data-bs-toggle="dropdown">
                    <span class="g-avatar" style="width:28px;height:28px;font-size:.7rem;">{{ $initials($gUser) }}</span>
                    <span>{{ $gUser->first_name }}</span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    @if(Route::has('guardian.account'))
                        <li><a class="dropdown-item" href="{{ route('guardian.account') }}"><i class="bi bi-person-circle me-2"></i>My account</a></li>
                        <li><hr class="dropdown-divider"></li>
                    @endif
                    <li>
                        <form method="POST" action="{{ route('logout') }}">@csrf
                            <button class="dropdown-item text-danger"><i class="bi bi-box-arrow-right me-2"></i>Log out</button>
                        </form>
                    </li>
                </ul>
            </div>
        </header>

        @include('partials.impersonation-banner')
        <main class="g-content">
            @if(session('success'))<div class="alert alert-success py-2">{{ session('success') }}</div>@endif
            @if(session('error'))<div class="alert alert-danger py-2">{{ session('error') }}</div>@endif
            @yield('content')
        </main>

        <footer class="g-foot">
            <span>© {{ date('Y') }} {{ $school }}. All rights reserved.</span>
            <span>{{ setting('motto') }}</span>
        </footer>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.getElementById('gToggle')?.addEventListener('click', () => document.getElementById('gShell').classList.toggle('open'));
</script>
@stack('scripts')
</body>
</html>
