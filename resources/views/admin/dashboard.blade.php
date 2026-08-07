@extends('layouts.app')
@section('title', 'Dashboard')

@section('content')
    <div class="mb-4">
        <h1 class="h4 mb-1">Welcome back, {{ auth()->user()->name }}</h1>
        <p class="text-muted mb-0">Here's what's happening at {{ $appSettings->school_name ?? 'your school' }} today.</p>
    </div>

    <div class="row g-3">
        <div class="col-6 col-md-3">
            <div class="app-stat-card d-flex align-items-center gap-3">
                <span class="stat-icon"><i class="bi bi-people"></i></span>
                <div><div class="text-muted small">Total Students</div><div class="h5 mb-0">--</div></div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="app-stat-card d-flex align-items-center gap-3">
                <span class="stat-icon" style="background: var(--brand-secondary);"><i class="bi bi-receipt"></i></span>
                <div><div class="text-muted small">Outstanding Invoices</div><div class="h5 mb-0">--</div></div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="app-stat-card d-flex align-items-center gap-3">
                <span class="stat-icon" style="background:#198754;"><i class="bi bi-cash-stack"></i></span>
                <div><div class="text-muted small">Collected This Term</div><div class="h5 mb-0">--</div></div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="app-stat-card d-flex align-items-center gap-3">
                <span class="stat-icon" style="background:#dc3545;"><i class="bi bi-clipboard-check"></i></span>
                <div><div class="text-muted small">Pending Applications</div><div class="h5 mb-0">--</div></div>
            </div>
        </div>
    </div>
@endsection
