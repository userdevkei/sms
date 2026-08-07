<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Sign In') - {{ setting('school_name', config('app.name')) }}</title>

    @if(setting('favicon_path'))
        <link rel="icon" href="{{ route('file', ['path' => setting('favicon_path')]) }}">
    @endif

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('css/auth.css') }}">

    <style>
        :root {
            --brand-primary: {{ setting('primary_color', '#0B3D62') }};
            --brand-secondary: {{ setting('secondary_color', '#0E8388') }};
        }
    </style>
</head>
<body class="auth-body">
<div class="auth-wrapper">
    <div class="auth-brand-panel">
        <div class="auth-brand-content">
            @if(setting('logo_path'))
                <img src="{{ route('file', ['path' => setting('logo_path')]) }}"
                     alt="{{ setting('school_name') }}" class="auth-brand-logo">
            @else
                <div class="auth-brand-logo-placeholder">
                    <i class="bi bi-mortarboard"></i>
                </div>
            @endif

            <h1 class="auth-brand-name">{{ setting('school_name', config('app.name')) }}</h1>

            @if(setting('tagline'))
                <p class="auth-brand-tagline">{{ setting('tagline') }}</p>
            @endif

            @if(setting('motto'))
                <div class="auth-brand-motto">
                    <i class="bi bi-quote"></i>
                    <span>{{ setting('motto') }}</span>
                </div>
            @endif
        </div>

        <div class="auth-brand-shape-1"></div>
        <div class="auth-brand-shape-2"></div>
    </div>

    <div class="auth-form-panel">
        <div class="auth-form-panel-inner">
            @yield('content')
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="{{ asset('js/auth.js') }}"></script>
</body>
</html>
