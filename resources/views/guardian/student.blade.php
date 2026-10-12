@extends('guardian.layout')
@use('App\Services\Guardian\StudentSnapshot')
@section('title', $child->full_name)

@push('styles')
    <style>
        /* ── Hero ── */
        .sd-hero { position:relative; overflow:hidden; border-radius:1rem; color:#fff; padding:1.5rem 1.6rem;
            background:linear-gradient(120deg,var(--g-blue-dark) 0%,var(--g-blue) 55%,var(--g-accent) 100%);
            box-shadow:0 10px 30px -12px rgba(11,74,124,.55); }
        .sd-hero::before, .sd-hero::after { content:""; position:absolute; border-radius:50%; background:rgba(255,255,255,.07); pointer-events:none; }
        .sd-hero::before { width:260px; height:260px; right:-70px; top:-110px; }
        .sd-hero::after  { width:180px; height:180px; right:120px; bottom:-120px; }
        .sd-hero > * { position:relative; z-index:1; }
        .sd-avatar { width:64px; height:64px; border-radius:1rem; background:rgba(255,255,255,.18); border:2px solid rgba(255,255,255,.35);
            display:grid; place-items:center; font-size:1.4rem; font-weight:700; letter-spacing:.02em; flex:none; }
        .sd-name { font-size:1.55rem; font-weight:700; line-height:1.15; margin:0; }
        .sd-meta { display:flex; flex-wrap:wrap; gap:.5rem; margin-top:.55rem; }
        .sd-chip { display:inline-flex; align-items:center; gap:.4rem; background:rgba(255,255,255,.14); border:1px solid rgba(255,255,255,.22);
            border-radius:999px; padding:.22rem .7rem; font-size:.78rem; font-weight:500; white-space:nowrap; }
        .sd-chip-danger { background:#fdecea; color:#b42318; border-color:#fdecea; font-weight:600; }
        .sd-chip-ok { background:#e4f4ea; color:#15803d; border-color:#e4f4ea; font-weight:600; }
        .sd-hero-note { font-size:.8rem; opacity:.8; }

        /* ── Stat cards ── */
        .sd-stat { display:flex; gap:.9rem; align-items:flex-start; height:100%; padding:1.05rem 1.1rem; background:#fff;
            border:1px solid var(--g-line); border-radius:.9rem; transition:box-shadow .15s, transform .15s; }
        .sd-stat:hover { box-shadow:0 8px 22px -14px rgba(16,24,40,.35); transform:translateY(-1px); }
        .sd-ico { width:44px; height:44px; border-radius:.8rem; display:grid; place-items:center; font-size:1.15rem; flex:none; }
        .sd-ico-blue   { background:#e7effa; color:#0b4a7c; }
        .sd-ico-green  { background:#e4f4ea; color:#15803d; }
        .sd-ico-amber  { background:#fff3d6; color:#b7791f; }
        .sd-ico-red    { background:#fdecea; color:#b42318; }
        .sd-ico-indigo { background:#e8eafb; color:#2b3aa0; }
        .sd-stat .lbl { color:var(--g-muted); font-size:.75rem; text-transform:uppercase; letter-spacing:.05em; font-weight:600; margin-bottom:.2rem; }
        .sd-stat .val { font-size:1.05rem; font-weight:700; line-height:1.25; }
        .sd-stat .sub { font-size:.78rem; color:var(--g-muted); margin-top:.15rem; }

        /* ── Section cards ── */
        .sd-card { background:#fff; border:1px solid var(--g-line); border-radius:.9rem; height:100%; overflow:hidden; display:flex; flex-direction:column; }
        .sd-card-head { display:flex; align-items:center; gap:.6rem; padding:.95rem 1.15rem; border-bottom:1px solid var(--g-line); }
        .sd-card-head .t { font-weight:700; font-size:.95rem; }
        .sd-card-head .ico { width:30px; height:30px; border-radius:.55rem; display:grid; place-items:center; background:#e7effa; color:var(--g-blue); font-size:.95rem; }
        .sd-viewall { margin-left:auto; font-size:.8rem; font-weight:600; text-decoration:none; color:var(--g-accent); display:inline-flex; align-items:center; gap:.25rem; }
        .sd-viewall:hover { text-decoration:underline; }
        .sd-card-foot { margin-top:auto; padding:.8rem 1.15rem; background:#f8fafc; border-top:1px solid var(--g-line); font-size:.8rem; color:var(--g-muted); }

        /* ── Tables ── */
        .sd-table { margin:0; }
        .sd-table thead th { background:#f8fafc; font-size:.7rem; text-transform:uppercase; letter-spacing:.05em; color:var(--g-muted); font-weight:600; border-bottom:1px solid var(--g-line); padding:.65rem .9rem; white-space:nowrap; }
        .sd-table tbody td { padding:.8rem .9rem; border-color:#f0f2f6; vertical-align:middle; }
        .sd-table tbody tr:hover td { background:#fafbfd; }
        .sd-pay-ico { width:32px; height:32px; border-radius:50%; background:#e4f4ea; color:#15803d; display:grid; place-items:center; flex:none; }
        .sd-empty { text-align:center; padding:2.2rem 1rem; color:var(--g-muted); }
        .sd-empty i { display:block; font-size:1.8rem; margin-bottom:.4rem; opacity:.45; }

        /* ── Quick links ── */
        .sd-link { display:flex; align-items:center; gap:.8rem; padding:.9rem 1rem; background:#fff; border:1px solid var(--g-line); border-radius:.8rem;
            color:var(--g-ink); text-decoration:none; font-weight:600; transition:all .15s; height:100%; }
        .sd-link i.lead { width:38px; height:38px; border-radius:.7rem; display:grid; place-items:center; background:#e7effa; color:var(--g-blue); font-size:1.05rem; flex:none; transition:all .15s; }
        .sd-link .chev { margin-left:auto; opacity:.35; transition:all .15s; }
        .sd-link:hover { border-color:var(--g-blue); color:var(--g-blue); box-shadow:0 8px 20px -14px rgba(11,74,124,.6); transform:translateY(-1px); }
        .sd-link:hover i.lead { background:var(--g-blue); color:#fff; }
        .sd-link:hover .chev { opacity:1; transform:translateX(2px); }

        .sd-section-title { font-size:.8rem; text-transform:uppercase; letter-spacing:.08em; color:var(--g-muted); font-weight:700; margin:0 0 .75rem; }

        @media (max-width:575px) {
            .sd-hero { padding:1.15rem; }
            .sd-avatar { width:52px; height:52px; font-size:1.15rem; }
            .sd-name { font-size:1.25rem; }
        }
    </style>
@endpush

@section('content')
    @php
        $e = $snap['enrollment'];
        $paidOn = StudentSnapshot::paidOn($snap['last_payment']);
        $initials = \Illuminate\Support\Str::upper(
            \Illuminate\Support\Str::substr($child->first_name, 0, 1) . \Illuminate\Support\Str::substr($child->last_name, 0, 1)
        );
        $gradeName  = $e?->gradeLevel?->name;
        $streamName = $e?->stream?->name;

        /*
         * Payments are not tied to individual invoices, so no invoice is ever marked paid/unpaid here.
         * Whether the family owes anything is decided by the ACCOUNT balance only.
         * The balance partial is rendered once and reused, so the flag below always agrees with what
         * the guardian sees in the "Account balance" card (no assumption about the sign convention).
         */
        $balanceHtml = view('guardian.partials.balance', ['balance' => $snap['balance']])->render();
        $inCredit    = stripos(strip_tags($balanceHtml), 'credit') !== false;
        $isOverdue   = ! $inCredit && ! empty($snap['overdue']);
    @endphp

    {{-- Hero --}}
    <div class="sd-hero mb-4">
        <div class="d-flex flex-wrap align-items-center gap-3">
            <div class="sd-avatar">{{ $initials }}</div>
            <div class="flex-grow-1">
                <h1 class="sd-name">{{ $child->full_name }}</h1>
                <div class="sd-meta">
                    <span class="sd-chip"><i class="bi bi-person-badge"></i> {{ $child->userID }}</span>
                    @if($gradeName)
                        <span class="sd-chip"><i class="bi bi-mortarboard"></i> {{ $gradeName }}{{ $streamName ? ' · ' . $streamName : '' }}</span>
                    @endif
                    @if($isOverdue)
                        <span class="sd-chip sd-chip-danger"><i class="bi bi-exclamation-triangle-fill"></i> Payment overdue</span>
                    @elseif($inCredit)
                        <span class="sd-chip sd-chip-ok"><i class="bi bi-check-circle-fill"></i> Account in credit</span>
                    @endif
                </div>
            </div>
            <div class="sd-hero-note d-none d-md-block text-end">
                <i class="bi bi-eye me-1"></i> You're viewing this student's account
            </div>
        </div>
    </div>

    {{-- Key figures --}}
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="sd-stat">
                <div class="sd-ico sd-ico-indigo"><i class="bi bi-mortarboard"></i></div>
                <div>
                    <div class="lbl">Grade / Stream</div>
                    <div class="val">{{ $gradeName ?? '—' }}{{ $streamName ? ' / ' . $streamName : '' }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="sd-stat">
                <div class="sd-ico sd-ico-blue"><i class="bi bi-wallet2"></i></div>
                <div>
                    <div class="lbl">Account balance</div>
                    {!! $balanceHtml !!}
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="sd-stat">
                <div class="sd-ico {{ $isOverdue ? 'sd-ico-red' : 'sd-ico-amber' }}"><i class="bi bi-calendar-event"></i></div>
                <div>
                    <div class="lbl">Next due date</div>
                    <div class="val {{ $isOverdue ? 'text-danger' : '' }}">{{ $snap['next_due']?->format('d M Y') ?? '—' }}</div>
                    @if($isOverdue)<div class="sub text-danger fw-semibold">Overdue</div>@endif
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="sd-stat">
                <div class="sd-ico sd-ico-green"><i class="bi bi-check2-circle"></i></div>
                <div>
                    <div class="lbl">Last payment</div>
                    <div class="val">{{ $snap['last_payment'] ? 'KES ' . number_format(StudentSnapshot::paidAmount($snap['last_payment'])) : '—' }}</div>
                    @if($paidOn)<div class="sub">{{ $paidOn->format('d M Y') }}</div>@endif
                </div>
            </div>
        </div>
    </div>

    {{-- Quick links --}}
    @if($quickLinks)
        <h2 class="sd-section-title"><i class="bi bi-lightning-charge-fill me-1"></i> Quick links</h2>
        <div class="row g-3 mb-4">
            @foreach($quickLinks as $link)
                <div class="col-12 col-sm-6 col-xl-4">
                    <a href="{{ $link['url'] }}" class="sd-link">
                        <i class="bi {{ $link['icon'] }} lead"></i>
                        <span>{{ $link['label'] }}</span>
                        <i class="bi bi-chevron-right chev"></i>
                    </a>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Invoices & payments --}}
    <div class="row g-3">
        <div class="col-lg-7">
            <div class="sd-card">
                <div class="sd-card-head">
                    <span class="ico"><i class="bi bi-receipt"></i></span>
                    <span class="t">Invoices</span>
                    @if(Route::has('guardian.students.invoices'))
                        <a class="sd-viewall" href="{{ route('guardian.students.invoices', $child->id) }}">View all <i class="bi bi-arrow-right"></i></a>
                    @endif
                </div>
                <div class="table-responsive">
                    <table class="table sd-table align-middle">
                        <thead>
                        <tr>
                            <th class="ps-4">Invoice</th>
                            <th>Due</th>
                            <th class="text-end pe-4">Amount (KES)</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($openInvoices as $inv)
                            <tr>
                                <td class="ps-4 fw-semibold">{{ $inv->invoice_number ?? $inv->id }}</td>
                                <td>{{ StudentSnapshot::dueOn($inv)?->format('d M Y') ?? '—' }}</td>
                                <td class="text-end pe-4 fw-bold">{{ number_format($inv->balance, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3"><div class="sd-empty"><i class="bi bi-receipt"></i>No invoices to show.</div></td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="sd-card-foot">
                    <i class="bi bi-info-circle me-1"></i>Payments are applied to the account as a whole, not to individual invoices. Your account balance above shows what is owed.
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="sd-card">
                <div class="sd-card-head">
                    <span class="ico"><i class="bi bi-cash-coin"></i></span>
                    <span class="t">Recent payments</span>
                    @if(Route::has('guardian.students.payments'))
                        <a class="sd-viewall" href="{{ route('guardian.students.payments', $child->id) }}">View all <i class="bi bi-arrow-right"></i></a>
                    @endif
                </div>
                <div class="table-responsive">
                    <table class="table sd-table align-middle">
                        <thead>
                        <tr><th class="ps-4">Payment</th><th class="text-end pe-4">Amount (KES)</th></tr>
                        </thead>
                        <tbody>
                        @forelse($recentPayments as $p)
                            <tr>
                                <td class="ps-4">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="sd-pay-ico"><i class="bi bi-arrow-down-left"></i></span>
                                        <span class="fw-medium">{{ StudentSnapshot::paidOn($p)?->format('d M Y') ?? '—' }}</span>
                                    </div>
                                </td>
                                <td class="text-end pe-4 fw-bold text-success">{{ number_format(StudentSnapshot::paidAmount($p), 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="2"><div class="sd-empty"><i class="bi bi-wallet2"></i>No payments yet.</div></td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
