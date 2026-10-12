@extends('guardian.layout')
@use('App\Services\Guardian\StudentSnapshot')
@section('title', 'Dashboard')

@push('styles')
    <style>
        /* ── Welcome hero ── */
        .gd-hero { position:relative; overflow:hidden; border-radius:1rem; color:#fff; padding:1.6rem 1.7rem;
            background:linear-gradient(120deg,var(--g-blue-dark) 0%,var(--g-blue) 55%,var(--g-accent) 100%);
            box-shadow:0 10px 30px -12px rgba(11,74,124,.55); }
        .gd-hero::before, .gd-hero::after { content:""; position:absolute; border-radius:50%; background:rgba(255,255,255,.07); pointer-events:none; }
        .gd-hero::before { width:280px; height:280px; right:-80px; top:-120px; }
        .gd-hero::after  { width:190px; height:190px; right:130px; bottom:-130px; }
        .gd-hero > * { position:relative; z-index:1; }
        .gd-hello { font-size:.85rem; opacity:.8; margin-bottom:.15rem; }
        .gd-title { font-size:1.65rem; font-weight:700; line-height:1.15; margin:0; }
        .gd-lead  { margin:.45rem 0 0; opacity:.85; font-size:.92rem; max-width:560px; }
        .gd-date  { background:rgba(255,255,255,.14); border:1px solid rgba(255,255,255,.22); border-radius:.8rem; padding:.55rem .9rem; text-align:center; line-height:1.2; }
        .gd-date b { display:block; font-size:1.25rem; }
        .gd-date span { font-size:.72rem; text-transform:uppercase; letter-spacing:.06em; opacity:.85; }

        /* ── Stat cards ── */
        .gd-stat { display:flex; gap:.9rem; align-items:center; height:100%; padding:1.05rem 1.1rem; background:#fff;
            border:1px solid var(--g-line); border-radius:.9rem; transition:box-shadow .15s, transform .15s; }
        .gd-stat:hover { box-shadow:0 8px 22px -14px rgba(16,24,40,.35); transform:translateY(-1px); }
        .gd-ico { width:46px; height:46px; border-radius:.85rem; display:grid; place-items:center; font-size:1.2rem; flex:none; }
        .gd-ico-blue   { background:#e7effa; color:#0b4a7c; }
        .gd-ico-green  { background:#e4f4ea; color:#15803d; }
        .gd-ico-amber  { background:#fff3d6; color:#b7791f; }
        .gd-ico-red    { background:#fdecea; color:#b42318; }
        .gd-ico-indigo { background:#e8eafb; color:#2b3aa0; }
        .gd-stat .lbl { color:var(--g-muted); font-size:.72rem; text-transform:uppercase; letter-spacing:.05em; font-weight:600; }
        .gd-stat .val { font-size:1.2rem; font-weight:700; line-height:1.25; }

        .gd-section { display:flex; align-items:center; gap:.6rem; margin:0 0 .85rem; }
        .gd-section h2 { font-size:.8rem; text-transform:uppercase; letter-spacing:.08em; color:var(--g-muted); font-weight:700; margin:0; }
        .gd-section .count { background:#e7effa; color:var(--g-blue); border-radius:999px; font-size:.72rem; font-weight:700; padding:.1rem .55rem; }

        /* ── Student cards ── */
        .gd-child { position:relative; background:#fff; border:1px solid var(--g-line); border-radius:1rem; height:100%; display:flex; flex-direction:column;
            overflow:hidden; transition:box-shadow .18s, transform .18s; }
        .gd-child:hover { box-shadow:0 14px 30px -18px rgba(11,74,124,.55); transform:translateY(-2px); }
        .gd-child::before { content:""; position:absolute; left:0; right:0; top:0; height:4px; background:linear-gradient(90deg,var(--g-blue),var(--g-accent)); }
        .gd-child.is-overdue::before { background:linear-gradient(90deg,#d92d20,#f97066); }
        .gd-child.is-credit::before  { background:linear-gradient(90deg,#15803d,#4ade80); }
        .gd-child-body { padding:1.25rem 1.2rem 1rem; flex:1; }
        .gd-avatar { width:50px; height:50px; border-radius:.9rem; background:linear-gradient(135deg,#dbe7ff,#c7d6fb); color:var(--g-accent);
            display:grid; place-items:center; font-weight:700; font-size:1rem; flex:none; }
        .gd-cname { font-weight:700; font-size:1rem; line-height:1.2; }
        .gd-cmeta { font-size:.78rem; color:var(--g-muted); }
        .gd-rel { margin-left:auto; font-size:.68rem; font-weight:600; text-transform:uppercase; letter-spacing:.05em; color:#4b5563; background:#eef1f6; border-radius:999px; padding:.2rem .6rem; white-space:nowrap; }
        .gd-status { display:inline-flex; align-items:center; gap:.35rem; font-size:.72rem; font-weight:600; border-radius:999px; padding:.2rem .65rem; }
        .gd-status-danger { background:#fdecea; color:#b42318; }
        .gd-status-ok     { background:#e4f4ea; color:#15803d; }
        .gd-balance { margin:1rem 0 .9rem; padding:.85rem 1rem; background:#f8fafc; border:1px dashed var(--g-line); border-radius:.8rem; }
        .gd-balance-lbl { font-size:.68rem; text-transform:uppercase; letter-spacing:.06em; color:var(--g-muted); font-weight:700; margin-bottom:.35rem; }
        .gd-mini { background:#f8fafc; border:1px solid var(--g-line); border-radius:.75rem; padding:.7rem .85rem; height:100%; }
        .gd-mini .lbl { font-size:.68rem; text-transform:uppercase; letter-spacing:.05em; color:var(--g-muted); font-weight:600; display:flex; align-items:center; gap:.3rem; }
        .gd-mini .val { font-weight:700; margin-top:.2rem; font-size:.95rem; }
        .gd-mini small { color:var(--g-muted); font-size:.72rem; }
        .gd-child-foot { display:flex; gap:.5rem; padding:.8rem 1.2rem 1.1rem; }
        .gd-btn { display:inline-flex; align-items:center; justify-content:center; gap:.4rem; font-weight:600; font-size:.85rem; border-radius:.65rem; padding:.55rem .9rem; text-decoration:none; transition:all .15s; }
        .gd-btn-primary { flex:1; background:var(--g-blue); color:#fff; border:1px solid var(--g-blue); }
        .gd-btn-primary:hover { background:var(--g-blue-dark); color:#fff; }
        .gd-btn-pay { background:#e4f4ea; color:#15803d; border:1px solid #c6e8d1; }
        .gd-btn-pay:hover { background:#15803d; color:#fff; }

        /* ── Recent payments ── */
        .gd-card { background:#fff; border:1px solid var(--g-line); border-radius:1rem; overflow:hidden; }
        .gd-card-head { display:flex; align-items:center; gap:.6rem; padding:.95rem 1.2rem; border-bottom:1px solid var(--g-line); }
        .gd-card-head .ico { width:30px; height:30px; border-radius:.55rem; display:grid; place-items:center; background:#e7effa; color:var(--g-blue); }
        .gd-card-head .t { font-weight:700; font-size:.95rem; }
        .gd-table { margin:0; }
        .gd-table thead th { background:#f8fafc; font-size:.7rem; text-transform:uppercase; letter-spacing:.05em; color:var(--g-muted); font-weight:600; border-bottom:1px solid var(--g-line); padding:.65rem .9rem; white-space:nowrap; }
        .gd-table tbody td { padding:.8rem .9rem; border-color:#f0f2f6; vertical-align:middle; }
        .gd-table tbody tr:hover td { background:#fafbfd; }
        .gd-pav { width:32px; height:32px; border-radius:.6rem; background:#e8eafb; color:var(--g-accent); display:grid; place-items:center; font-size:.72rem; font-weight:700; flex:none; }

        .gd-empty { text-align:center; padding:3rem 1rem; background:#fff; border:1px dashed var(--g-line); border-radius:1rem; color:var(--g-muted); }
        .gd-empty i { font-size:2.2rem; display:block; opacity:.4; margin-bottom:.5rem; }

        @media (max-width:575px) {
            .gd-hero { padding:1.2rem; }
            .gd-title { font-size:1.3rem; }
        }
    </style>
@endpush

@section('content')
    @php
        $hour     = now()->hour;
        $greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
        $mkInit   = fn ($u) => \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($u->first_name, 0, 1) . \Illuminate\Support\Str::substr($u->last_name, 0, 1));
    @endphp

    {{-- Welcome --}}
    <div class="gd-hero mb-4">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
                <div class="gd-hello">{{ $greeting }}</div>
                <h1 class="gd-title">Welcome, {{ $guardian->first_name }}</h1>
                <p class="gd-lead">Here's an overview of your {{ $totals['students'] === 1 ? "child's account" : "children's accounts" }} — balances, upcoming dues and recent payments in one place.</p>
            </div>
            <div class="gd-date d-none d-sm-block">
                <b>{{ now()->format('d') }}</b>
                <span>{{ now()->format('M Y') }}</span>
            </div>
        </div>
    </div>

    {{-- Totals --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3">
            <div class="gd-stat">
                <div class="gd-ico gd-ico-indigo"><i class="bi bi-people"></i></div>
                <div><div class="lbl">Students</div><div class="val">{{ $totals['students'] }}</div></div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="gd-stat">
                <div class="gd-ico {{ $totals['outstanding'] > 0 ? 'gd-ico-red' : 'gd-ico-green' }}"><i class="bi bi-exclamation-circle"></i></div>
                <div>
                    <div class="lbl">Total outstanding</div>
                    <div class="val {{ $totals['outstanding'] > 0 ? 'text-danger' : 'text-success' }}">KES {{ number_format($totals['outstanding'], 2) }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="gd-stat">
                <div class="gd-ico gd-ico-green"><i class="bi bi-piggy-bank"></i></div>
                <div><div class="lbl">Credit balance</div><div class="val text-success">KES {{ number_format($totals['credit'], 2) }}</div></div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="gd-stat">
                <div class="gd-ico gd-ico-amber"><i class="bi bi-calendar-event"></i></div>
                <div><div class="lbl">Next due date</div><div class="val">{{ $totals['next_due']?->format('d M Y') ?? '—' }}</div></div>
            </div>
        </div>
    </div>

    {{-- Students --}}
    <div class="gd-section">
        <h2><i class="bi bi-mortarboard-fill me-1"></i> Your students</h2>
        <span class="count">{{ $students->count() }}</span>
    </div>

    <div class="row g-3 mb-4">
        @forelse($students as $s)
            @php
                $d      = $snap[$s->id];
                $e      = $d['enrollment'];
                $class  = trim(($e?->gradeLevel?->name ?? '') . ' ' . ($e?->stream?->name ?? ''));
                $paidOn = StudentSnapshot::paidOn($d['last_payment']);

                // Payments are not tied to invoices, so only the ACCOUNT balance decides overdue.
                // The partial is rendered once and reused so the flag always agrees with what is shown.
                $balanceHtml = view('guardian.partials.balance', ['balance' => $d['balance']])->render();
                $inCredit    = stripos(strip_tags($balanceHtml), 'credit') !== false;
                $isOverdue   = ! $inCredit && ! empty($d['overdue']);
            @endphp
            <div class="col-md-6 col-xl-4">
                <div class="gd-child {{ $isOverdue ? 'is-overdue' : ($inCredit ? 'is-credit' : '') }}">
                    <div class="gd-child-body">
                        <div class="d-flex align-items-center gap-3">
                            <span class="gd-avatar">{{ $mkInit($s) }}</span>
                            <div class="min-w-0">
                                <div class="gd-cname">{{ $s->full_name }}</div>
                                <div class="gd-cmeta">{{ $s->userID }}{{ $class ? ' · ' . $class : '' }}</div>
                            </div>
                            @if($s->pivot->relationship)
                                <span class="gd-rel">{{ $s->pivot->relationship }}</span>
                            @endif
                        </div>

                        @if($isOverdue)
                            <div class="mt-3"><span class="gd-status gd-status-danger"><i class="bi bi-exclamation-triangle-fill"></i> Payment overdue</span></div>
                        @elseif($inCredit)
                            <div class="mt-3"><span class="gd-status gd-status-ok"><i class="bi bi-check-circle-fill"></i> Account in credit</span></div>
                        @endif

                        <div class="gd-balance">
                            <div class="gd-balance-lbl">Account balance</div>
                            {!! $balanceHtml !!}
                        </div>

                        <div class="row g-2">
                            <div class="col-6">
                                <div class="gd-mini">
                                    <div class="lbl"><i class="bi bi-calendar-event"></i> Next due</div>
                                    <div class="val {{ $isOverdue ? 'text-danger' : '' }}">{{ $d['next_due']?->format('d M Y') ?? '—' }}</div>
                                    @if($isOverdue)<small class="text-danger fw-semibold">Overdue</small>@endif
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="gd-mini">
                                    <div class="lbl"><i class="bi bi-check2-circle"></i> Last payment</div>
                                    <div class="val">{{ $d['last_payment'] ? 'KES ' . number_format(StudentSnapshot::paidAmount($d['last_payment'])) : '—' }}</div>
                                    @if($paidOn)<small>{{ $paidOn->format('d M Y') }}</small>@endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="gd-child-foot">
                        <a href="{{ route('guardian.students.show', $s->id) }}" class="gd-btn gd-btn-primary">
                            Open account <i class="bi bi-arrow-right"></i>
                        </a>
                        @if(Route::has('guardian.students.pay') && $s->pivot->can_pay)
                            <a href="{{ route('guardian.students.pay', $s->id) }}" class="gd-btn gd-btn-pay"><i class="bi bi-phone"></i> Pay</a>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="gd-empty">
                    <i class="bi bi-person-x"></i>
                    <div class="fw-semibold text-body">No students linked yet</div>
                    <div class="small">No active students are linked to your account. Please contact the school office.</div>
                </div>
            </div>
        @endforelse
    </div>

    {{-- Recent payments --}}
    @if($recentPayments->isNotEmpty())
        <div class="gd-card">
            <div class="gd-card-head">
                <span class="ico"><i class="bi bi-cash-coin"></i></span>
                <span class="t">Recent payments</span>
            </div>
            <div class="table-responsive">
                <table class="table gd-table align-middle">
                    <thead>
                    <tr><th class="ps-4">Student</th><th>Date</th><th class="text-end pe-4">Amount (KES)</th></tr>
                    </thead>
                    <tbody>
                    @foreach($recentPayments as $p)
                        @php $payer = $students->firstWhere('id', $p->user_id); @endphp
                        <tr>
                            <td class="ps-4">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="gd-pav">{{ $payer ? $mkInit($payer) : '—' }}</span>
                                    <span class="fw-semibold">{{ $payer?->full_name ?? '—' }}</span>
                                </div>
                            </td>
                            <td>{{ StudentSnapshot::paidOn($p)?->format('d M Y') ?? '—' }}</td>
                            <td class="text-end pe-4 fw-bold text-success">{{ number_format(StudentSnapshot::paidAmount($p), 2) }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
@endsection
