@extends('layouts.app')
@section('title', 'User Profile')

@push('styles')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
@endpush

@section('content')
    <div class="profile-page">

        <div class="profile-topbar">
            <div>
                <p class="profile-eyebrow mb-1">Users / Directory</p>
                <h1 class="profile-title">{{ $user->first_name }} {{ $user->last_name }}</h1>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('users.index') }}" class="btn btn-kv-ghost">
                    <i class="bi bi-arrow-left"></i> Back to Users
                </a>
                <a href="{{ route('users.edit', $user->id) }}" class="btn btn-kv-solid">
                    <i class="bi bi-pencil-square"></i> Edit
                </a>
            </div>
        </div>

        @if(session('success'))
            <div class="kv-alert mb-3">{{ session('success') }}</div>
        @endif

        <div class="profile-layout">

            {{-- STICKY SIDEBAR: identity + fields a reader needs visible while scrolling the tabs below --}}
            <aside class="profile-sidebar">

                <div class="kv-card kv-panel">
                    <div class="kv-panel-body kv-identity-body">
                        <div class="kv-avatar-wrap">
                            @if($user->avatar)
                                <img src="{{ route('file', ['path' => $user->avatar]) }}" alt="{{ $user->first_name }}" class="kv-avatar-img">
                            @else
                                <div class="kv-avatar-fallback">
                                    {{ strtoupper(substr($user->first_name, 0, 1) . substr($user->last_name, 0, 1)) }}
                                </div>
                            @endif
                        </div>

                        <h2 class="kv-name">
                            {{ $user->first_name }} {{ $user->middle_name ? $user->middle_name.' ' : '' }}{{ $user->last_name }}
                        </h2>
                        <p class="kv-userid">{{ $user->userID }}</p>

                        <div class="kv-pills">
                            <span class="kv-pill kv-pill-role">
                                {{ $user->roles->pluck('name')->join(', ') ?: 'No role assigned' }}
                            </span>
                            <span class="kv-pill {{ $user->status === 'active' ? 'kv-pill-active' : 'kv-pill-inactive' }}">
                                <span class="kv-status-dot"></span> {{ ucfirst($user->status) }}
                            </span>
                        </div>

                        <form method="POST" action="{{ route('users.toggleStatus', $user->id) }}" class="mt-3">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn-kv-toggle w-100 justify-content-center {{ $user->status === 'active' ? 'is-danger' : 'is-success' }}">
                                {{ $user->status === 'active' ? 'Deactivate' : 'Activate' }}
                            </button>
                        </form>
                    </div>
                </div>

                <div class="kv-card kv-panel">
                    <div class="kv-panel-head"><h3>Personal Information</h3></div>
                    <div class="kv-panel-body">
                        <div class="kv-row">
                            <span class="kv-label">First Name</span>
                            <span class="kv-value">{{ $user->first_name }}</span>
                        </div>
                        <div class="kv-row">
                            <span class="kv-label">Middle Name</span>
                            <span class="kv-value">{{ $user->middle_name ?? '—' }}</span>
                        </div>
                        <div class="kv-row">
                            <span class="kv-label">Last Name</span>
                            <span class="kv-value">{{ $user->last_name }}</span>
                        </div>
                        <div class="kv-row">
                            <span class="kv-label">Gender</span>
                            <span class="kv-value">{{ $user->gender ? ucfirst($user->gender) : '—' }}</span>
                        </div>
                        <div class="kv-row">
                            <span class="kv-label">Date of Birth</span>
                            <span class="kv-value">{{ $user->date_of_birth ? \Carbon\Carbon::parse($user->date_of_birth)->format('d M Y') : '—' }}</span>
                        </div>
                        <div class="kv-row">
                            <span class="kv-label">Citizenship</span>
                            <span class="kv-value">{{ $user->citizenship ?? '—' }}</span>
                        </div>
                        <div class="kv-row kv-row-last">
                            <span class="kv-label">Ethnicity</span>
                            <span class="kv-value">{{ $user->ethnicity ?? '—' }}</span>
                        </div>
                    </div>
                </div>

                <div class="kv-card kv-panel">
                    <div class="kv-panel-head"><h3>Contact Information</h3></div>
                    <div class="kv-panel-body">
                        <div class="kv-row">
                            <span class="kv-label">Email</span>
                            <span class="kv-value">
                                <a href="mailto:{{ $user->email }}" class="kv-link">{{ $user->email }}</a>
                                @if($user->email_verified_at)
                                    <i class="bi bi-patch-check-fill kv-verified" title="Verified"></i>
                                @endif
                            </span>
                        </div>
                        <div class="kv-row kv-row-last">
                            <span class="kv-label">Phone</span>
                            <span class="kv-value">
                                @if($user->phone_number)
                                    <a href="tel:{{ $user->phone_number }}" class="kv-link">{{ $user->phone_number }}</a>
                                @else
                                    —
                                @endif
                            </span>
                        </div>
                    </div>
                </div>

                <div class="kv-card kv-panel">
                    <div class="kv-panel-head"><h3>Location</h3></div>
                    <div class="kv-panel-body">
                        <div class="kv-row">
                            <span class="kv-label">County</span>
                            <span class="kv-value">{{ $user->county ?? '—' }}</span>
                        </div>
                        <div class="kv-row">
                            <span class="kv-label">Sub County</span>
                            <span class="kv-value">{{ $user->sub_county ?? '—' }}</span>
                        </div>
                        <div class="kv-row kv-row-last">
                            <span class="kv-label">Ward</span>
                            <span class="kv-value">{{ $user->ward ?? '—' }}</span>
                        </div>
                    </div>
                </div>

            </aside>

            {{-- MAIN COLUMN: account facts, then role-specific tabs render here via profile_extensions --}}
            <div class="profile-main">

                <div class="kv-card kv-panel mb-4">
                    <div class="kv-panel-head"><h3>Account</h3></div>
                    <div class="kv-quick-grid">
                        <div class="kv-quick-item">
                            <div class="kv-quick-val kv-mono">{{ $user->userID }}</div>
                            <div class="kv-quick-lbl">User ID</div>
                        </div>
                        <div class="kv-quick-item">
                            <div class="kv-quick-val">{{ $user->roles->pluck('name')->join(', ') ?: '—' }}</div>
                            <div class="kv-quick-lbl">Role</div>
                        </div>
                        <div class="kv-quick-item">
                            <div class="kv-quick-val">{{ ucfirst($user->status) }}</div>
                            <div class="kv-quick-lbl">Status</div>
                        </div>
                        <div class="kv-quick-item">
                            <div class="kv-quick-val">{{ optional($user->created_at)->format('d M Y') ?? '—' }}</div>
                            <div class="kv-quick-lbl">Date Joined</div>
                        </div>
                        <div class="kv-quick-item">
                            <div class="kv-quick-val">{{ optional($user->updated_at)->format('d M Y') ?? '—' }}</div>
                            <div class="kv-quick-lbl">Last Updated</div>
                        </div>
                    </div>
                </div>

                @foreach(config('profile_extensions') as $slug => $view)
                    @if($user->roles->pluck('slug')->contains($slug) && view()->exists($view))
                        @include($view, ['user' => $user])
                    @endif
                @endforeach

            </div>

        </div>
    </div>

    <style>
        .profile-page {
            --kv-ink: #1A1D21;
            --kv-muted: #6B7280;
            --kv-line: #E4E6EA;
            --kv-surface: #FFFFFF;
            --kv-bg: #F7F8FA;
            --kv-accent: #2054C9;
            --kv-accent-tint: #EBF0FD;
            --kv-success: #15803D;
            --kv-success-tint: #E9F6ED;
            --kv-danger: #B42318;
            --kv-danger-tint: #FDECEA;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            color: var(--kv-ink);
            font-size: 14px;
        }

        .profile-page .profile-topbar {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            gap: .75rem;
        }
        .profile-page .profile-eyebrow {
            font-size: .72rem;
            font-weight: 600;
            letter-spacing: .04em;
            text-transform: uppercase;
            color: var(--kv-muted);
        }
        .profile-page .profile-title {
            font-weight: 600;
            font-size: 1.4rem;
            color: var(--kv-ink);
            margin: 0;
        }

        .profile-page .btn-kv-ghost,
        .profile-page .btn-kv-solid {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            font-size: .84rem;
            font-weight: 500;
            padding: .45rem .85rem;
            border-radius: .4rem;
            text-decoration: none;
            transition: background .12s ease;
        }
        .profile-page .btn-kv-ghost {
            color: var(--kv-ink);
            background: var(--kv-surface);
            border: 1px solid var(--kv-line);
        }
        .profile-page .btn-kv-ghost:hover { background: var(--kv-bg); color: var(--kv-ink); }
        .profile-page .btn-kv-solid {
            color: #fff;
            background: var(--kv-accent);
            border: 1px solid var(--kv-accent);
        }
        .profile-page .btn-kv-solid:hover { background: #17419C; color: #fff; }

        .profile-page .kv-alert {
            background: var(--kv-success-tint);
            color: var(--kv-success);
            border: 1px solid #C7EAD3;
            padding: .55rem .9rem;
            border-radius: .4rem;
            font-size: .86rem;
            font-weight: 500;
        }

        .profile-page .kv-card {
            background: var(--kv-surface);
            border: 1px solid var(--kv-line);
            border-radius: .5rem;
        }

        /* Sidebar + main layout */
        .profile-page .profile-layout {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 1rem;
            align-items: start;
        }
        .profile-page .profile-sidebar {
            position: sticky;
            top: 1rem;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        .profile-page .profile-main { min-width: 0; }
        @media (max-width: 900px) {
            .profile-page .profile-layout { grid-template-columns: 1fr; }
            .profile-page .profile-sidebar { position: static; }
        }

        /* Identity card (top of sidebar) */
        .profile-page .kv-identity-body { text-align: center; }
        .profile-page .kv-avatar-wrap { display: flex; justify-content: center; margin-bottom: .75rem; }
        .profile-page .kv-avatar-img,
        .profile-page .kv-avatar-fallback {
            width: 64px;
            height: 64px;
            border-radius: .5rem;
        }
        .profile-page .kv-avatar-img { object-fit: cover; }
        .profile-page .kv-avatar-fallback {
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--kv-accent-tint);
            color: var(--kv-accent);
            font-size: 1.1rem;
            font-weight: 600;
        }
        .profile-page .kv-name {
            font-weight: 600;
            font-size: 1.05rem;
            color: var(--kv-ink);
            margin: 0 0 .1rem;
        }
        .profile-page .kv-userid {
            font-size: .78rem;
            color: var(--kv-muted);
            font-weight: 500;
            margin: 0 0 .6rem;
        }
        .profile-page .kv-pills { display: flex; flex-wrap: wrap; justify-content: center; gap: .4rem; }
        .profile-page .kv-pill {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            font-size: .74rem;
            font-weight: 600;
            padding: .2rem .55rem;
            border-radius: .3rem;
        }
        .profile-page .kv-pill-role { background: var(--kv-accent-tint); color: var(--kv-accent); }
        .profile-page .kv-pill-active { background: var(--kv-success-tint); color: var(--kv-success); }
        .profile-page .kv-pill-inactive { background: var(--kv-bg); color: var(--kv-muted); }
        .profile-page .kv-status-dot { width: 6px; height: 6px; border-radius: 50%; background: currentColor; }

        .profile-page .btn-kv-toggle {
            display: inline-flex;
            font-size: .82rem;
            font-weight: 500;
            padding: .5rem .85rem;
            border-radius: .4rem;
            background: #fff;
            transition: background .12s ease;
        }
        .profile-page .btn-kv-toggle.is-danger { color: var(--kv-danger); border: 1px solid #F2C6C2; }
        .profile-page .btn-kv-toggle.is-danger:hover { background: var(--kv-danger-tint); }
        .profile-page .btn-kv-toggle.is-success { color: var(--kv-success); border: 1px solid #BFE6CC; }
        .profile-page .btn-kv-toggle.is-success:hover { background: var(--kv-success-tint); }

        .profile-page .kv-panel-head {
            padding: .9rem 1.1rem;
            border-bottom: 1px solid var(--kv-line);
        }
        .profile-page .kv-panel-head h3 {
            font-size: .76rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .04em;
            color: var(--kv-muted);
            margin: 0;
        }
        .profile-page .kv-panel-body { padding: 1rem 1.1rem; }
        .profile-page .kv-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: .6rem 0;
            border-bottom: 1px solid var(--kv-line);
        }
        .profile-page .kv-row-last { border-bottom: none; }
        .profile-page .kv-label { font-size: .84rem; color: var(--kv-muted); font-weight: 400; white-space: nowrap; }
        .profile-page .kv-value { font-size: .86rem; font-weight: 500; color: var(--kv-ink); text-align: right; }
        .profile-page .kv-mono { font-variant-numeric: tabular-nums; letter-spacing: .01em; }
        .profile-page .kv-link { color: var(--kv-accent); text-decoration: none; font-weight: 500; }
        .profile-page .kv-link:hover { text-decoration: underline; }
        .profile-page .kv-verified { color: var(--kv-success); margin-left: .3rem; font-size: .8rem; }

        /* Account quick-fact grid (main column) */
        .profile-page .kv-quick-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 0;
        }
        .profile-page .kv-quick-item {
            padding: 1rem 1.1rem;
            border-right: 1px solid var(--kv-line);
            border-bottom: 1px solid var(--kv-line);
        }
        .profile-page .kv-quick-item:last-child { border-right: none; }
        .profile-page .kv-quick-val { font-size: .95rem; font-weight: 600; color: var(--kv-ink); }
        .profile-page .kv-quick-lbl { font-size: .74rem; color: var(--kv-muted); margin-top: .15rem; }
        @media (max-width: 560px) {
            .profile-page .kv-quick-item:nth-child(2n) { border-right: none; }
        }
    </style>
@endsection
