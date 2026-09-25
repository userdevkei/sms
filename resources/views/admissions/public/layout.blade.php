<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', $appSettings->get('school_name', config('app.name')))</title>

    @if($appSettings->get('favicon_path'))
        <link rel="icon" href="{{ route('file', ['path' => $appSettings->get('favicon_path')]) }}">
    @endif
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        /*:root { --ad-border: #e8ecf3; --ad-muted: #64748b; --ad-ink: #0f172a; }*/

        :root {
            --brand-primary: {{ $appSettings->get('primary_color', '#0B3D62') }};
            --brand-secondary: {{ $appSettings->get('secondary_color', '#0E8388') }};
            --brand-sidebar: {{ $appSettings->get('sidebar_color', '#0B3D62') }};
            --ad-border: #e8ecf3; --ad-muted: #64748b; --ad-ink: #0f172a;
        }

        body { background: #f4f6fb; color: var(--app-ink); min-height: 100vh; display: flex; flex-direction: column; }
        .app-header { background: #fff; border-bottom: 1px solid var(--app-border); }
        .app-header .inner { max-width: 920px; margin: 0 auto; padding: .9rem 1rem; display: flex; align-items: center; justify-content: space-between; gap: 1rem; }
        .app-brand { display: flex; align-items: center; gap: .6rem; font-weight: 700; color: var(--app-ink); text-decoration: none; }
        .app-brand i { width: 36px; height: 36px; border-radius: 10px; display: grid; place-items: center; background: rgba(var(--bs-primary-rgb), .1); color: var(--bs-primary); font-size: 1.15rem; }
        .app-shell { width: 100%; max-width: 920px; margin: 0 auto; padding: 1.75rem 1rem 3rem; flex: 1; }
        .app-card { background: #fff; border: 1px solid var(--app-border); border-radius: 16px; box-shadow: 0 1px 3px rgba(15, 23, 42, .05); }
        .app-footer { text-align: center; color: var(--app-muted); font-size: .82rem; padding: 1.25rem 1rem; }

        .form-label { font-weight: 600; font-size: .88rem; margin-bottom: .35rem; }
        .form-control, .form-select { padding: .65rem .85rem; border-radius: 10px; border-color: #d8deea; }
        .form-control:focus, .form-select:focus { box-shadow: 0 0 0 4px rgba(var(--bs-primary-rgb), .12); }
        .btn { border-radius: 10px; font-weight: 600; padding: .6rem 1.15rem; }
        .btn-lg { padding: .8rem 1.5rem; }
        .app-section-title { font-size: .72rem; letter-spacing: .09em; text-transform: uppercase; color: var(--app-muted); font-weight: 700; margin: 1.75rem 0 .75rem; }
        .app-code { font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; letter-spacing: .12em; }

        /* stepper */
        .stepper { display: flex; margin: 0 0 1.5rem; }
        .stepper .s { flex: 1; text-align: center; position: relative; text-decoration: none; color: inherit; }
        .stepper .s::before { content: ''; position: absolute; top: 17px; left: -50%; width: 100%; height: 2px; background: #dfe4ee; z-index: 0; }
        .stepper .s:first-child::before { display: none; }
        .stepper .s.done::before, .stepper .s.current::before { background: var(--bs-primary); }
        .stepper .dot { position: relative; z-index: 1; width: 36px; height: 36px; border-radius: 50%; margin: 0 auto 6px; display: grid; place-items: center; font-weight: 700; font-size: .9rem; background: #fff; border: 2px solid #dfe4ee; color: #94a3b8; }
        .stepper .s.done .dot { background: var(--bs-primary); border-color: var(--bs-primary); color: #fff; }
        .stepper .s.current .dot { border-color: var(--bs-primary); color: var(--bs-primary); box-shadow: 0 0 0 4px rgba(var(--bs-primary-rgb), .15); }
        .stepper .lbl { font-size: .78rem; color: var(--app-muted); font-weight: 600; }
        .stepper .s.current .lbl { color: var(--app-ink); }
        @media (max-width: 575.98px) {
            .stepper { margin-bottom: 2.75rem; }
            .stepper .lbl { display: none; }
            .stepper .s.current .lbl { display: block; position: absolute; top: 44px; left: 50%; transform: translateX(-50%); white-space: nowrap; }
        }
    </style>
    @stack('styles')
</head>
<body>
<header class="app-header">
    <div class="inner">
        <a href="{{ route('apply.landing') }}" class="app-brand"><i class="bi bi-mortarboard-fill"></i><span>{{ setting('school_name') }}</span></a>
        <span class="text-muted small d-none d-sm-inline">Admissions</span>
    </div>
</header>

<main class="app-shell">
    @foreach (['success' => 'success', 'info' => 'info', 'error' => 'danger'] as $flash => $tone)
        @if (session($flash))
            <div class="alert alert-{{ $tone }} d-flex align-items-start gap-2" role="alert">
                <i class="bi bi-info-circle mt-1"></i><div>{{ session($flash) }}</div>
            </div>
        @endif
    @endforeach

    @yield('content')
</main>

<div class="app-footer">&copy; {{ date('Y') }} {{ setting('school_name') }}</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')
</body>
</html>
