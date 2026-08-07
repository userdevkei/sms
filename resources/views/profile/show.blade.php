@extends('layouts.app')
@section('title', 'My Profile')

@section('content')
    <div class="profile-header d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div class="d-flex align-items-center gap-3">
            <div class="profile-avatar">
                {{ strtoupper(substr($user->first_name ?? 'U', 0, 1) . substr($user->last_name ?? '', 0, 1)) }}
            </div>
            <div>
                <h1 class="h5 mb-1 fw-semibold">{{ $user->full_name }}</h1>
                <span class="profile-role-badge text-capitalize">{{ $user->roles()->first()->name ?? 'User' }}</span>
            </div>
        </div>
        @unless($user->hasRole('student'))
            <a href="{{ route('profile.edit') }}" class="btn btn-primary btn-sm px-3">
                <i class="bi bi-pencil me-1"></i> Edit Profile
            </a>
        @endunless
    </div>

    <div class="kv-card kv-panel">
        <div class="kv-panel-head">
            <span class="kv-panel-icon"><i class="bi bi-person"></i></span>
            <h3>Personal Details</h3>
        </div>
        <div class="kv-panel-body">
            <div class="kv-grid">
                <div class="kv-field">
                    <span class="kv-field-label"><i class="bi bi-person-badge"></i> Full Name</span>
                    <span class="kv-field-value">{{ $user->full_name }}</span>
                </div>
                <div class="kv-field">
                    <span class="kv-field-label"><i class="bi bi-envelope"></i> Email</span>
                    <span class="kv-field-value">{{ $user->email ?? '—' }}</span>
                </div>
                <div class="kv-field">
                    <span class="kv-field-label"><i class="bi bi-telephone"></i> Phone</span>
                    <span class="kv-field-value">{{ $user->phone_number ?? '—' }}</span>
                </div>
                <div class="kv-field">
                    <span class="kv-field-label"><i class="bi bi-gender-ambiguous"></i> Gender</span>
                    <span class="kv-field-value text-capitalize">{{ $user->gender ?? '—' }}</span>
                </div>
                <div class="kv-field">
                    <span class="kv-field-label"><i class="bi bi-cake2"></i> Date of Birth</span>
                    <span class="kv-field-value">{{ $user->date_of_birth?->format('d M Y') ?? '—' }}</span>
                </div>
                <div class="kv-field">
                    <span class="kv-field-label"><i class="bi bi-geo-alt"></i> County / Sub-County</span>
                    <span class="kv-field-value">{{ $user->county ?? '—' }} / {{ $user->sub_county ?? '—' }}</span>
                </div>
            </div>
        </div>
    </div>

    @if($user->hasRole('student'))
        @include('profile.partials.student')
    @elseif($user->hasRole('driver'))
{{--        @include('profile.partials.driver')--}}
    @endif
@endsection

@push('styles')
    <style>
        .profile-avatar {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background: #2054C9;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 1.1rem;
            letter-spacing: 0.5px;
            flex-shrink: 0;
        }

        .profile-role-badge {
            display: inline-block;
            font-size: 0.72rem;
            font-weight: 600;
            letter-spacing: 0.3px;
            padding: 0.2rem 0.6rem;
            border-radius: 999px;
            background: rgba(32, 84, 201, 0.1);
            color: #2054C9;
        }

        .kv-card {
            background: #fff;
            border: 1px solid #e9ecef;
            border-radius: 10px;
            margin-bottom: 1.25rem;
        }

        .kv-panel-head {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            padding: 1rem 1.25rem;
            border-bottom: 1px solid #f0f1f3;
        }

        .kv-panel-head h3 {
            font-size: 0.95rem;
            font-weight: 600;
            margin: 0;
            color: #1a1a1a;
        }

        .kv-panel-icon {
            width: 30px;
            height: 30px;
            border-radius: 8px;
            background: rgba(32, 84, 201, 0.08);
            color: #2054C9;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
            flex-shrink: 0;
        }

        .kv-panel-body {
            padding: 1.25rem;
        }

        .kv-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1.1rem 1.5rem;
        }

        .kv-field {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .kv-field-label {
            font-size: 0.72rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            color: #8a8f98;
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }

        .kv-field-value {
            font-size: 0.92rem;
            font-weight: 500;
            color: #1a1a1a;
        }

        /* Simple single-row fallback (used by Accommodation/Bus Route "current" rows) */
        .kv-row {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            font-size: 0.9rem;
        }

        .kv-row .kv-label {
            font-weight: 600;
            color: #6b7280;
            display: flex;
            align-items: center;
            gap: 0.4rem;
            min-width: 140px;
        }

        .kv-row .kv-value {
            color: #1a1a1a;
            font-weight: 500;
        }

        .kv-panel-body table.dataTable {
            font-size: 0.85rem;
        }

        .kv-panel-body table.dataTable thead th {
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            color: #8a8f98;
            border-bottom: 1px solid #e9ecef;
        }
    </style>
@endpush
