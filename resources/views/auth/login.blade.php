@extends('layouts.guest')
@section('title', 'Sign In')

@section('content')
    <div class="auth-mobile-brand d-lg-none">
        @if(setting('logo_path'))
            <img src="{{ route('file', ['path' => setting('logo_path')]) }}" alt="{{ setting('school_name') }}">
        @else
            <i class="bi bi-mortarboard"></i>
        @endif
        <span>{{ setting('school_name', config('app.name')) }}</span>
    </div>

    <div class="auth-form-header">
        <h2>Welcome back</h2>
        <p class="text-muted">Sign in to continue to your dashboard.</p>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger d-flex align-items-start gap-2" role="alert">
            <i class="bi bi-exclamation-triangle-fill mt-1"></i>
            <div>
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        </div>
    @endif

    @if (session('status'))
        <div class="alert alert-success" role="alert">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('login') }}" novalidate>
        @csrf

        <div class="mb-3">
            <label for="email" class="form-label">Email Address</label>
            <div class="auth-input-group">
                <span class="auth-input-icon"><i class="bi bi-envelope"></i></span>
                <input type="email" id="email" name="email"
                       class="form-control auth-input @error('email') is-invalid @enderror"
                       value="{{ old('email') }}"
                       placeholder="you@school.ac.ke"
                       autocomplete="username" autofocus required>
            </div>
        </div>

        <div class="mb-2">
            <div class="d-flex justify-content-between align-items-center">
                <label for="password" class="form-label mb-0">Password</label>
                @if(Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="auth-forgot-link">Forgot password?</a>
                @endif
            </div>
            <div class="auth-input-group">
                <span class="auth-input-icon"><i class="bi bi-lock"></i></span>
                <input type="password" id="password" name="password"
                       class="form-control auth-input @error('password') is-invalid @enderror"
                       placeholder="Enter your password"
                       autocomplete="current-password" required>
                <button type="button" class="auth-password-toggle" aria-label="Show password" tabindex="-1">
                    <i class="bi bi-eye"></i>
                </button>
            </div>
        </div>

        <div class="form-check mb-4 mt-3">
            <input class="form-check-input" type="checkbox" id="remember" name="remember">
            <label class="form-check-label text-muted" for="remember">
                Keep me signed in on this device
            </label>
        </div>

        <button type="submit" class="btn auth-submit-btn w-100">
            Sign In <i class="bi bi-arrow-right ms-1"></i>
        </button>
    </form>

    <p class="auth-help-text">
        <i class="bi bi-info-circle"></i>
        Having trouble signing in? Contact your school administrator
        @if(setting('phone')) at <strong>{{ setting('phone') }}</strong> @endif
        @if(setting('email')) or <strong>{{ setting('email') }}</strong> @endif.
    </p>
@endsection
