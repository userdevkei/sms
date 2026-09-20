@if (session()->has('impersonator_id'))
    @php
        $impUser = auth()->user();
        $impName = $impUser->name ?? $impUser->full_name ?? $impUser->email ?? 'this user';
    @endphp

    {{-- Deliberately NOT using .alert / .toast / .fade: global "auto-dismiss alerts" scripts target those. --}}
    <style>
        body { padding-bottom: 80px !important; }

        #impersonation-bar {
            position: fixed;
            left: 50%;
            bottom: 16px;
            transform: translateX(-50%);
            z-index: 2000;
            display: flex;
            align-items: center;
            gap: 14px;
            max-width: calc(100vw - 24px);
            padding: 8px 8px 8px 18px;
            background: #111827;
            color: #f9fafb;
            border: 1px solid #f59e0b;
            border-radius: 999px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, .35);
            font-size: .9rem;
            line-height: 1.2;
        }
        #impersonation-bar .imp-dot {
            width: 10px; height: 10px; flex: 0 0 10px;
            border-radius: 50%;
            background: #f59e0b;
            animation: imp-pulse 1.6s infinite;
        }
        #impersonation-bar .imp-label { color: #fcd34d; font-size: .75rem; text-transform: uppercase; letter-spacing: .06em; }
        #impersonation-bar .imp-name  { font-weight: 600; }
        #impersonation-bar .imp-exit {
            border: 0;
            border-radius: 999px;
            padding: 8px 16px;
            background: #f59e0b;
            color: #111827;
            font-weight: 600;
            white-space: nowrap;
            cursor: pointer;
            transition: background .15s;
        }
        #impersonation-bar .imp-exit:hover { background: #fbbf24; }

        @keyframes imp-pulse {
            0%   { box-shadow: 0 0 0 0 rgba(245, 158, 11, .6); }
            70%  { box-shadow: 0 0 0 8px rgba(245, 158, 11, 0); }
            100% { box-shadow: 0 0 0 0 rgba(245, 158, 11, 0); }
        }

        @media (max-width: 575.98px) {
            #impersonation-bar { gap: 10px; padding-left: 14px; }
            #impersonation-bar .imp-label { display: none; }
        }
    </style>

    <div id="impersonation-bar" role="status">
        <span class="imp-dot"></span>
        <span>
            <span class="imp-label d-block">Impersonating</span>
            <span class="imp-name">{{ $impName }}</span>
        </span>
        <form method="POST" action="{{ route('impersonate.stop') }}" class="m-0">
            @csrf
            <button type="submit" class="imp-exit">Return to my account</button>
        </form>
    </div>
@endif
