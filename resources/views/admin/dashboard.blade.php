@extends('layouts.app')
@section('title', 'Dashboard')

@push('styles')
    <style>
        .app-stat-card{background:#fff;border-radius:.75rem;padding:1rem 1.25rem;box-shadow:0 1px 3px rgba(0,0,0,.06);height:100%;}
        .stat-icon{width:48px;height:48px;border-radius:.65rem;display:flex;align-items:center;justify-content:center;background:var(--brand-primary,#0d6efd);color:#fff;font-size:1.25rem;flex-shrink:0;}
        .dash-section{background:#fff;border-radius:.75rem;padding:1.25rem;box-shadow:0 1px 3px rgba(0,0,0,.06);margin-bottom:1.5rem;}
        .dash-section h6{font-weight:600;margin-bottom:1rem;display:flex;align-items:center;gap:.4rem;}
        .quick-link{display:flex;align-items:center;gap:.6rem;padding:.65rem .9rem;border-radius:.5rem;background:#f8f9fa;color:#212529;text-decoration:none;transition:.15s;font-size:.9rem;}
        .quick-link:hover{background:var(--brand-primary,#0d6efd);color:#fff;}
        .chart-box{position:relative;height:260px;}
        .chart-box canvas{max-width:100%;}
        .balance-badge{display:inline-flex;align-items:center;gap:.4rem;padding:.3rem .75rem;border-radius:2rem;font-weight:600;font-size:.85rem;}
        .balance-badge.owed{background:#f8d7da;color:#842029;}
        .balance-badge.credit{background:#d1e7dd;color:#0f5132;}
        .balance-badge.settled{background:#e9ecef;color:#495057;}
        @media (max-width:576px){.chart-box{height:220px;}}
    </style>
@endpush

@php
    // Shared helper: render a colour-coded balance badge from a signed amount.
    // > 0 = still owed (red), < 0 = credit/overpayment (green), 0 = settled (grey).
    function balanceBadge($signedAmount) {
        $amount = number_format(abs($signedAmount));
        if ($signedAmount > 0) {
            return "<span class=\"balance-badge owed\"><i class=\"bi bi-exclamation-circle\"></i> Owes KES {$amount}</span>";
        } elseif ($signedAmount < 0) {
            return "<span class=\"balance-badge credit\"><i class=\"bi bi-check-circle\"></i> Credit KES {$amount}</span>";
        }
        return "<span class=\"balance-badge settled\"><i class=\"bi bi-dash-circle\"></i> Settled</span>";
    }
@endphp

@section('content')
    <div class="mb-4">
        <h1 class="h4 mb-1">Welcome back, {{ auth()->user()->first_name }}</h1>
        <p class="text-muted mb-0">
            Here's what's happening at {{ $appSettings->school_name ?? 'your school' }} today{{ $currentTerm ? " — {$currentTerm->academic_year} Term {$currentTerm->term_number}" : '' }}.
        </p>
    </div>

    {{-- ================= LEADERSHIP: super_admin / admin ================= --}}
    @if(isset($adminStats))
        <div class="dash-section">
            <h6><i class="bi bi-speedometer2"></i> School Overview</h6>
            <div class="row g-3 mb-4">
                <div class="col-6 col-md-3">
                    <div class="app-stat-card d-flex align-items-center gap-3">
                        <span class="stat-icon"><i class="bi bi-people"></i></span>
                        <div><div class="text-muted small">Total Students</div><div class="h5 mb-0">{{ number_format($adminStats['total_students']) }}</div></div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="app-stat-card d-flex align-items-center gap-3">
                        <span class="stat-icon" style="background:#6f42c1;"><i class="bi bi-person-badge"></i></span>
                        <div><div class="text-muted small">Total Staff</div><div class="h5 mb-0">{{ number_format($adminStats['total_staff']) }}</div></div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="app-stat-card d-flex align-items-center gap-3">
                        <span class="stat-icon" style="background:#dc3545;"><i class="bi bi-exclamation-circle"></i></span>
                        <div><div class="text-muted small">Outstanding Balance</div><div class="h5 mb-0 text-danger">KES {{ number_format($adminStats['outstanding_balance']) }}</div></div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="app-stat-card d-flex align-items-center gap-3">
                        <span class="stat-icon" style="background:#198754;"><i class="bi bi-piggy-bank"></i></span>
                        <div><div class="text-muted small">Credit Balances</div><div class="h5 mb-0 text-success">KES {{ number_format($adminStats['credit_balance']) }}</div></div>
                    </div>
                </div>
            </div>
            <div class="row g-3 mb-4">
                <div class="col-6 col-md-3">
                    <div class="app-stat-card d-flex align-items-center gap-3">
                        <span class="stat-icon" style="background:#0dcaf0;"><i class="bi bi-cash-stack"></i></span>
                        <div><div class="text-muted small">Collected This Term</div><div class="h5 mb-0">KES {{ number_format($adminStats['collected_this_term']) }}</div></div>
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-12 col-lg-4"><div class="chart-box"><canvas id="chartGender"></canvas></div></div>
                <div class="col-12 col-lg-4"><div class="chart-box"><canvas id="chartGrade"></canvas></div></div>
                <div class="col-12 col-lg-4"><div class="chart-box"><canvas id="chartCounty"></canvas></div></div>
            </div>
            <div class="row g-3">
                <div class="col-12 col-lg-6"><div class="chart-box"><canvas id="chartCollectionsTrend"></canvas></div></div>
                <div class="col-12 col-lg-6"><div class="chart-box"><canvas id="chartPaymentMethod"></canvas></div></div>
            </div>

            <hr class="my-4">
            <h6><i class="bi bi-lightning-charge"></i> Quick Links</h6>
            <div class="row g-2">
                <div class="col-6 col-md-3"><a href="{{ route('students.index') }}" class="quick-link"><i class="bi bi-people"></i> Students</a></div>
                <div class="col-6 col-md-3"><a href="{{ route('finance.invoices.index') }}" class="quick-link"><i class="bi bi-receipt"></i> Invoices</a></div>
                <div class="col-6 col-md-3"><a href="{{ route('finance.fee-structures.index') }}" class="quick-link"><i class="bi bi-cash-coin"></i> Fee Structures</a></div>
                <div class="col-6 col-md-3"><a href="{{ route('users.index') }}" class="quick-link"><i class="bi bi-person-lines-fill"></i> Users</a></div>
                <div class="col-6 col-md-3"><a href="{{ route('accommodation.hostels.index') }}" class="quick-link"><i class="bi bi-building"></i> Hostels</a></div>
                <div class="col-6 col-md-3"><a href="{{ route('transport.transport-routes.index') }}" class="quick-link"><i class="bi bi-signpost-split"></i> Transport</a></div>
            </div>
        </div>
    @endif

    {{-- ================= FINANCE: finance_officer / accountant ================= --}}
    @if(isset($financeStats))
        <div class="dash-section">
            <h6><i class="bi bi-wallet2"></i> Finance</h6>
            <div class="row g-3 mb-4">
                <div class="col-6 col-md-3">
                    <div class="app-stat-card d-flex align-items-center gap-3">
                        <span class="stat-icon"><i class="bi bi-file-earmark-text"></i></span>
                        <div><div class="text-muted small">Invoiced This Term</div><div class="h5 mb-0">KES {{ number_format($financeStats['invoiced_this_term']) }}</div></div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="app-stat-card d-flex align-items-center gap-3">
                        <span class="stat-icon" style="background:#0dcaf0;"><i class="bi bi-cash-stack"></i></span>
                        <div><div class="text-muted small">Collected This Term</div><div class="h5 mb-0">KES {{ number_format($financeStats['collected_this_term']) }}</div></div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="app-stat-card d-flex align-items-center gap-3">
                        <span class="stat-icon" style="background:#dc3545;"><i class="bi bi-exclamation-circle"></i></span>
                        <div><div class="text-muted small">Outstanding</div><div class="h5 mb-0 text-danger">KES {{ number_format($financeStats['outstanding_balance']) }}</div></div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="app-stat-card d-flex align-items-center gap-3">
                        <span class="stat-icon" style="background:#198754;"><i class="bi bi-piggy-bank"></i></span>
                        <div><div class="text-muted small">Credit Balances</div><div class="h5 mb-0 text-success">KES {{ number_format($financeStats['credit_balance']) }}</div></div>
                    </div>
                </div>
            </div>
            <div class="row g-3 mb-3">
                <div class="col-6 col-md-3">
                    <div class="app-stat-card d-flex align-items-center gap-3">
                        <span class="stat-icon" style="background:#fd7e14;"><i class="bi bi-graph-up-arrow"></i></span>
                        <div><div class="text-muted small">Collection Rate</div><div class="h5 mb-0">{{ $financeStats['collection_rate'] }}%</div></div>
                    </div>
                </div>
            </div>
            <div class="row g-3 mb-3">
                <div class="col-12 col-lg-6"><div class="chart-box"><canvas id="chartCollectionsTrendFin"></canvas></div></div>
                <div class="col-12 col-lg-6"><div class="chart-box"><canvas id="chartInvoiceStatus"></canvas></div></div>
            </div>
            <div class="row g-3">
                <div class="col-12 col-lg-6"><div class="chart-box"><canvas id="chartPaymentMethodFin"></canvas></div></div>
                <div class="col-12 col-lg-6"><div class="chart-box"><canvas id="chartOutstandingByGrade"></canvas></div></div>
            </div>

            <hr class="my-4">
            <h6><i class="bi bi-lightning-charge"></i> Quick Links</h6>
            <div class="row g-2">
                <div class="col-6 col-md-3"><a href="{{ route('finance.invoices.index') }}" class="quick-link"><i class="bi bi-receipt"></i> Invoices</a></div>
                <div class="col-6 col-md-3"><a href="{{ route('finance.payments.index') }}" class="quick-link"><i class="bi bi-credit-card"></i> Payments</a></div>
                <div class="col-6 col-md-3"><a href="{{ route('finance.fee-structures.index') }}" class="quick-link"><i class="bi bi-cash-coin"></i> Fee Structures</a></div>
                <div class="col-6 col-md-3"><a href="{{ route('finance.bank-transactions.index') }}" class="quick-link"><i class="bi bi-bank"></i> Bank Reconciliation</a></div>
            </div>
        </div>
    @endif

    {{-- ================= ACADEMIC STAFF: class_teacher / teacher / exam_coodrinator ================= --}}
    @if(isset($academicStats))
        <div class="dash-section">
            <h6><i class="bi bi-easel"></i> My Class</h6>
            <div class="row g-3 mb-3">
                <div class="col-6 col-md-3">
                    <div class="app-stat-card d-flex align-items-center gap-3">
                        <span class="stat-icon"><i class="bi bi-diagram-3"></i></span>
                        <div><div class="text-muted small">My Streams</div><div class="h5 mb-0">{{ $academicStats['my_streams'] }}</div></div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="app-stat-card d-flex align-items-center gap-3">
                        <span class="stat-icon" style="background:#6f42c1;"><i class="bi bi-people"></i></span>
                        <div><div class="text-muted small">My Students</div><div class="h5 mb-0">{{ $academicStats['my_students'] }}</div></div>
                    </div>
                </div>
            </div>
            @if($academicStats['my_streams'] > 0)
                <div class="row g-3">
                    <div class="col-12 col-lg-5"><div class="chart-box"><canvas id="chartMyGender"></canvas></div></div>
                </div>
            @else
                <p class="text-muted small mb-0">You're not assigned as a class teacher to any stream yet.</p>
            @endif

            <hr class="my-4">
            <h6><i class="bi bi-lightning-charge"></i> Quick Links</h6>
            <div class="row g-2">
                <div class="col-6 col-md-3"><a href="#" class="quick-link"><i class="bi bi-people"></i> My Students</a></div>
                <div class="col-6 col-md-3"><a href="#" class="quick-link"><i class="bi bi-clipboard-check"></i> Results Entry</a></div>
                <div class="col-6 col-md-3"><a href="#" class="quick-link"><i class="bi bi-calendar-check"></i> Attendance</a></div>
            </div>
        </div>
    @endif

    {{-- ================= REGISTRAR ================= --}}
    @if(isset($registrarStats))
        <div class="dash-section">
            <h6><i class="bi bi-journal-bookmark"></i> Admissions & Enrollment</h6>
            <div class="row g-3 mb-3">
                <div class="col-6 col-md-4">
                    <div class="app-stat-card d-flex align-items-center gap-3">
                        <span class="stat-icon"><i class="bi bi-people"></i></span>
                        <div><div class="text-muted small">Enrollments This Year</div><div class="h5 mb-0">{{ $registrarStats['total_enrollments_year'] }}</div></div>
                    </div>
                </div>
                <div class="col-6 col-md-4">
                    <div class="app-stat-card d-flex align-items-center gap-3">
                        <span class="stat-icon" style="background:#198754;"><i class="bi bi-person-plus"></i></span>
                        <div><div class="text-muted small">New This Term</div><div class="h5 mb-0">{{ $registrarStats['new_this_term'] }}</div></div>
                    </div>
                </div>
                <div class="col-6 col-md-4">
                    <div class="app-stat-card d-flex align-items-center gap-3">
                        <span class="stat-icon" style="background:#dc3545;"><i class="bi bi-hourglass-split"></i></span>
                        <div><div class="text-muted small">Pending Exceptions</div><div class="h5 mb-0">{{ $registrarStats['pending_exceptions'] }}</div></div>
                    </div>
                </div>
            </div>
            <div class="row g-3">
                <div class="col-12 col-lg-7"><div class="chart-box"><canvas id="chartRegByGrade"></canvas></div></div>
                <div class="col-12 col-lg-5"><div class="chart-box"><canvas id="chartExceptionsType"></canvas></div></div>
            </div>

            <hr class="my-4">
            <h6><i class="bi bi-lightning-charge"></i> Quick Links</h6>
            <div class="row g-2">
                <div class="col-6 col-md-3"><a href="{{ route('student-enrollments.index') }}" class="quick-link"><i class="bi bi-person-lines-fill"></i> Enrollments</a></div>
                <div class="col-6 col-md-3"><a href="{{ route('progression-exceptions.index') }}" class="quick-link"><i class="bi bi-arrow-repeat"></i> Progression</a></div>
                <div class="col-6 col-md-3"><a href="{{ route('grade-levels.index') }}" class="quick-link"><i class="bi bi-bar-chart-steps"></i> Grade Levels</a></div>
                <div class="col-6 col-md-3"><a href="{{ route('streams.index') }}" class="quick-link"><i class="bi bi-diagram-3"></i> Streams</a></div>
            </div>
        </div>
    @endif

    {{-- ================= HR OFFICER ================= --}}
    @if(isset($hrStats))
        <div class="dash-section">
            <h6><i class="bi bi-person-workspace"></i> Human Resources</h6>
            <div class="row g-3 mb-3">
                <div class="col-6 col-md-3">
                    <div class="app-stat-card d-flex align-items-center gap-3">
                        <span class="stat-icon"><i class="bi bi-person-badge"></i></span>
                        <div><div class="text-muted small">Total Staff</div><div class="h5 mb-0">{{ $hrStats['total_staff'] }}</div></div>
                    </div>
                </div>
            </div>
            <div class="row g-3">
                <div class="col-12 col-lg-5"><div class="chart-box"><canvas id="chartStaffGender"></canvas></div></div>
                <div class="col-12 col-lg-7"><div class="chart-box"><canvas id="chartStaffByRole"></canvas></div></div>
            </div>

            <hr class="my-4">
            <h6><i class="bi bi-lightning-charge"></i> Quick Links</h6>
            <div class="row g-2">
                <div class="col-6 col-md-3"><a href="{{ route('users.index') }}" class="quick-link"><i class="bi bi-person-lines-fill"></i> Staff Directory</a></div>
                <div class="col-6 col-md-3"><a href="{{ route('roles.index') }}" class="quick-link"><i class="bi bi-shield-lock"></i> Roles</a></div>
            </div>
        </div>
    @endif

    {{-- ================= HOSTEL WARDEN ================= --}}
    @if(isset($hostelStats))
        <div class="dash-section">
            <h6><i class="bi bi-building"></i> Accommodation</h6>
            <div class="row g-3 mb-3">
                <div class="col-6 col-md-3">
                    <div class="app-stat-card d-flex align-items-center gap-3">
                        <span class="stat-icon"><i class="bi bi-house-check"></i></span>
                        <div><div class="text-muted small">Active Allocations</div><div class="h5 mb-0">{{ $hostelStats['active_allocations'] }}</div></div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="app-stat-card d-flex align-items-center gap-3">
                        <span class="stat-icon" style="background:#dc3545;"><i class="bi bi-hourglass-split"></i></span>
                        <div><div class="text-muted small">Pending Reservations</div><div class="h5 mb-0">{{ $hostelStats['pending_reservations'] }}</div></div>
                    </div>
                </div>
            </div>
            <div class="row g-3">
                <div class="col-12 col-lg-6"><div class="chart-box"><canvas id="chartOccupancy"></canvas></div></div>
                <div class="col-12 col-lg-6"><div class="chart-box"><canvas id="chartReservationStatus"></canvas></div></div>
            </div>

            <hr class="my-4">
            <h6><i class="bi bi-lightning-charge"></i> Quick Links</h6>
            <div class="row g-2">
                <div class="col-6 col-md-3"><a href="{{ route('accommodation.hostels.index') }}" class="quick-link"><i class="bi bi-building"></i> Hostels</a></div>
                <div class="col-6 col-md-3"><a href="{{ route('accommodation.room-allocations.index') }}" class="quick-link"><i class="bi bi-door-open"></i> Room Allocations</a></div>
                <div class="col-6 col-md-3"><a href="{{ route('accommodation.room-reservations.index') }}" class="quick-link"><i class="bi bi-calendar2-check"></i> Reservations</a></div>
            </div>
        </div>
    @endif

    {{-- ================= DRIVER ================= --}}
    @if(isset($driverStats))
        <div class="dash-section">
            <h6><i class="bi bi-truck"></i> My Vehicle</h6>
            @if($driverStats['vehicle'])
                <div class="row g-3">
                    <div class="col-6 col-md-3">
                        <div class="app-stat-card">
                            <div class="text-muted small">Registration</div>
                            <div class="h6 mb-0">{{ $driverStats['vehicle']->registration_number }}</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="app-stat-card">
                            <div class="text-muted small">Capacity</div>
                            <div class="h6 mb-0">{{ $driverStats['vehicle']->capacity }}</div>
                        </div>
                    </div>
                </div>
            @else
                <p class="text-muted small mb-0">No vehicle assigned yet — this needs a driver↔vehicle link on your side.</p>
            @endif
        </div>
    @endif

    {{-- ================= PARENT ================= --}}
    @if(isset($parentStats))
        <div class="dash-section">
            <h6><i class="bi bi-people"></i> My Children</h6>
            <div class="row g-3 mb-3">
                <div class="col-6 col-md-3">
                    <div class="app-stat-card d-flex align-items-center gap-3">
                        <span class="stat-icon"><i class="bi bi-person-hearts"></i></span>
                        <div><div class="text-muted small">Children</div><div class="h5 mb-0">{{ $parentStats['children_count'] }}</div></div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="app-stat-card d-flex align-items-center gap-3">
                        <span class="stat-icon" style="background:#dc3545;"><i class="bi bi-exclamation-circle"></i></span>
                        <div><div class="text-muted small">Outstanding Total</div><div class="h5 mb-0 text-danger">KES {{ number_format($parentStats['outstanding_total']) }}</div></div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="app-stat-card d-flex align-items-center gap-3">
                        <span class="stat-icon" style="background:#198754;"><i class="bi bi-piggy-bank"></i></span>
                        <div><div class="text-muted small">Credit Total</div><div class="h5 mb-0 text-success">KES {{ number_format($parentStats['credit_total']) }}</div></div>
                    </div>
                </div>
            </div>

            @if($parentStats['children_count'] > 0)
                <div class="row g-2 mb-3">
                    @foreach($children as $child)
                        <div class="col-12 col-md-6">
                            <div class="d-flex align-items-center justify-content-between app-stat-card">
                                <span>{{ $child->first_name }} {{ $child->last_name }}</span>
                                {!! balanceBadge($parentCharts['balance_by_child'][$child->first_name ?? $child->id] ?? 0) !!}
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="row g-3">
                    <div class="col-12 col-lg-6"><div class="chart-box"><canvas id="chartBalanceByChild"></canvas></div></div>
                </div>
            @endif

            <hr class="my-4">
            <h6><i class="bi bi-lightning-charge"></i> Quick Links</h6>
            <div class="row g-2">
                <div class="col-6 col-md-3"><a href="#" class="quick-link"><i class="bi bi-wallet2"></i> Pay Fees</a></div>
                <div class="col-6 col-md-3"><a href="#" class="quick-link"><i class="bi bi-clipboard-data"></i> View Results</a></div>
                <div class="col-6 col-md-3"><a href="#" class="quick-link"><i class="bi bi-file-earmark-text"></i> Statements</a></div>
            </div>
        </div>
    @endif

    {{-- ================= STUDENT ================= --}}
    @if(isset($studentStats))
        <div class="dash-section">
            <h6><i class="bi bi-mortarboard"></i> My Overview</h6>
            <div class="row g-3 align-items-stretch mb-3">
                <div class="col-6 col-md-3">
                    <div class="app-stat-card">
                        <div class="text-muted small">Grade / Stream</div>
                        <div class="h6 mb-0">{{ $studentStats['grade'] ?? '—' }} {{ $studentStats['stream'] ? '/ '.$studentStats['stream'] : '' }}</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="app-stat-card d-flex flex-column justify-content-center">
                        <div class="text-muted small mb-1">Account Balance</div>
                        {!! balanceBadge($studentStats['balance']) !!}
                    </div>
                </div>
                @if($studentStats['next_due_invoice'])
                    <div class="col-6 col-md-3">
                        <div class="app-stat-card">
                            <div class="text-muted small">Next Due Date</div>
                            <div class="h6 mb-0">{{ \Illuminate\Support\Carbon::parse($studentStats['next_due_invoice']->due_date)->format('d M Y') }}</div>
                        </div>
                    </div>
                @endif
                @if($studentStats['last_payment'])
                    <div class="col-6 col-md-3">
                        <div class="app-stat-card">
                            <div class="text-muted small">Last Payment</div>
                            <div class="h6 mb-0">KES {{ number_format($studentStats['last_payment']->amount) }} &middot; {{ \Illuminate\Support\Carbon::parse($studentStats['last_payment']->paid_on)->format('d M Y') }}</div>
                        </div>
                    </div>
                @endif
            </div>

            <hr class="my-4">
            <h6><i class="bi bi-lightning-charge"></i> Quick Links</h6>
            <div class="row g-2">
                <div class="col-6 col-md-3"><a href="{{ route('results.my-results.index') }}" class="quick-link"><i class="bi bi-clipboard-data"></i> My Results</a></div>
                <div class="col-6 col-md-3"><a href="{{ route('finance.my-statement') }}" class="quick-link"><i class="bi bi-file-earmark-text"></i> My Statement</a></div>
                <div class="col-6 col-md-3"><a href="{{ route('finance.my-payments') }}" class="quick-link"><i class="bi bi-wallet2"></i> My Payments</a></div>
            </div>
        </div>
    @endif

@endsection

@push('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.5.0/chart.umd.min.js" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const palette = ['#0d6efd','#6f42c1','#198754','#dc3545','#fd7e14','#0dcaf0','#ffc107','#6c757d'];
            const baseOpts = { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } } } };

            function makeChart(id, type, labels, data, opts = {}) {
                const el = document.getElementById(id);
                if (!el) return;
                new Chart(el, {
                    type,
                    data: {
                        labels,
                        datasets: [{ data, backgroundColor: palette, borderWidth: type === 'line' ? 2 : 0, tension: 0.3, fill: type === 'line' }]
                    },
                    options: { ...baseOpts, ...opts }
                });
            }

            // Chart with two side-by-side datasets (e.g. Outstanding vs Credit per grade)
            function makeGroupedChart(id, labels, datasets, opts = {}) {
                const el = document.getElementById(id);
                if (!el) return;
                new Chart(el, {
                    type: 'bar',
                    data: { labels, datasets },
                    options: { ...baseOpts, ...opts }
                });
            }

            @if(isset($adminCharts))
            makeChart('chartGender', 'doughnut', @json($adminCharts['gender_split']->keys()), @json($adminCharts['gender_split']->values()), { plugins: { title: { display: true, text: 'Student Gender Split' } } });
            makeChart('chartGrade', 'bar', @json($adminCharts['by_grade']->keys()), @json($adminCharts['by_grade']->values()), { plugins: { title: { display: true, text: 'Students by Grade' }, legend: { display: false } } });
            makeChart('chartCounty', 'bar', @json($adminCharts['by_county']->keys()), @json($adminCharts['by_county']->values()), { indexAxis: 'y', plugins: { title: { display: true, text: 'Top Counties' }, legend: { display: false } } });
            makeChart('chartCollectionsTrend', 'line', @json($adminCharts['collections_trend']->keys()), @json($adminCharts['collections_trend']->values()), { plugins: { title: { display: true, text: 'Collections Trend' }, legend: { display: false } } });
            makeChart('chartPaymentMethod', 'pie', @json($adminCharts['by_method']->keys()), @json($adminCharts['by_method']->values()), { plugins: { title: { display: true, text: 'Payments by Method' } } });
            @endif

            @if(isset($financeCharts))
            makeChart('chartCollectionsTrendFin', 'line', @json($financeCharts['collections_trend']->keys()), @json($financeCharts['collections_trend']->values()), { plugins: { title: { display: true, text: 'Collections Trend' }, legend: { display: false } } });
            makeChart('chartInvoiceStatus', 'doughnut', @json($financeCharts['invoice_status']->keys()), @json($financeCharts['invoice_status']->values()), { plugins: { title: { display: true, text: 'Invoice Status' } } });
            makeChart('chartPaymentMethodFin', 'pie', @json($financeCharts['by_method']->keys()), @json($financeCharts['by_method']->values()), { plugins: { title: { display: true, text: 'Payments by Method' } } });
            makeGroupedChart('chartOutstandingByGrade', @json($financeCharts['balance_by_grade_labels']), [
                { label: 'Outstanding', data: @json($financeCharts['outstanding_by_grade']), backgroundColor: '#dc3545' },
                { label: 'Credit',      data: @json($financeCharts['credit_by_grade']),      backgroundColor: '#198754' },
            ], { plugins: { title: { display: true, text: 'Balance by Grade' } } });
            @endif

            @if(isset($academicCharts) && $academicStats['my_streams'] > 0)
            makeChart('chartMyGender', 'doughnut', @json($academicCharts['my_gender_split']->keys()), @json($academicCharts['my_gender_split']->values()), { plugins: { title: { display: true, text: 'My Class Gender Split' } } });
            @endif

            @if(isset($registrarCharts))
            makeChart('chartRegByGrade', 'bar', @json($registrarCharts['by_grade']->keys()), @json($registrarCharts['by_grade']->values()), { plugins: { title: { display: true, text: 'Enrollments by Grade' }, legend: { display: false } } });
            makeChart('chartExceptionsType', 'doughnut', @json($registrarCharts['exceptions_by_type']->keys()), @json($registrarCharts['exceptions_by_type']->values()), { plugins: { title: { display: true, text: 'Progression Exceptions' } } });
            @endif

            @if(isset($hrCharts))
            makeChart('chartStaffGender', 'doughnut', @json($hrCharts['staff_gender']->keys()), @json($hrCharts['staff_gender']->values()), { plugins: { title: { display: true, text: 'Staff Gender Split' } } });
            makeChart('chartStaffByRole', 'bar', @json($hrCharts['staff_by_role']->keys()), @json($hrCharts['staff_by_role']->values()), { indexAxis: 'y', plugins: { title: { display: true, text: 'Staff by Role' }, legend: { display: false } } });
            @endif

            @if(isset($hostelCharts))
            makeChart('chartOccupancy', 'bar', @json($hostelCharts['occupancy_by_hostel']->keys()), @json($hostelCharts['occupancy_by_hostel']->values()), { plugins: { title: { display: true, text: 'Occupancy by Hostel' }, legend: { display: false } } });
            makeChart('chartReservationStatus', 'doughnut', @json($hostelCharts['reservation_status']->keys()), @json($hostelCharts['reservation_status']->values()), { plugins: { title: { display: true, text: 'Reservation Status' } } });
            @endif

            @if(isset($parentCharts) && $parentStats['children_count'] > 0)
            // Signed balances: colour each bar red (owed) or green (credit) individually.
            (function () {
                const labels = @json($parentCharts['balance_by_child']->keys());
                const values = @json($parentCharts['balance_by_child']->values());
                const colors = values.map(v => v > 0 ? '#dc3545' : (v < 0 ? '#198754' : '#6c757d'));
                const el = document.getElementById('chartBalanceByChild');
                if (el) {
                    new Chart(el, {
                        type: 'bar',
                        data: { labels, datasets: [{ data: values, backgroundColor: colors }] },
                        options: { ...baseOpts, plugins: { title: { display: true, text: 'Balance by Child (red = owed, green = credit)' }, legend: { display: false } } }
                    });
                }
            })();
            @endif
        });
    </script>
@endpush
