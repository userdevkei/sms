<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', $appSettings->get('school_name', config('app.name')))</title>

    @if($appSettings->get('favicon_path'))
        <link rel="icon" href="{{ route('file', ['path' => $appSettings->get('favicon_path')]) }}">
    @endif

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0/dist/css/select2.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">

    <style>
        :root {
            --brand-primary: {{ $appSettings->get('primary_color', '#0B3D62') }};
            --brand-secondary: {{ $appSettings->get('secondary_color', '#0E8388') }};
            --brand-sidebar: {{ $appSettings->get('sidebar_color', '#0B3D62') }};
        }
    </style>

    @stack('styles')
</head>
<body>
<div class="app-wrapper">
    @include('partials.sidebar')

    <div class="app-main">
        @include('partials.navbar')

        <div class="app-content">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert" style="font-size: 13px !important;">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show fs-6" role="alert" style="font-size: 13px !important;">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @yield('content')
        </div>

        @include('partials.footer')
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="{{ asset('js/app.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0/dist/js/select2.min.js"></script>

@stack('scripts')

</body>
</html>
