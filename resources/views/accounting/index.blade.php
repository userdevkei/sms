@extends('layouts.app')

@section('title', 'Accounting')
@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h4 mb-1">Accounting Overview</h1>
            <div class="text-muted small">Academic Year {{ $academicYear }}</div>
        </div>
        <a href="{{ route('accounting.reports.summary') }}" class="btn btn-primary btn-sm">
            <i class="bi bi-file-earmark-bar-graph me-1"></i> Full Report
        </a>
    </div>

    {{-- Stat cards --}}
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="text-muted small mb-1">Total Income</div>
                            <div class="fs-4 fw-bold text-success">{{ number_format($incomeTotal, 2) }}</div>
                        </div>
                        <div class="rounded-circle bg-success bg-opacity-10 text-success d-flex align-items-center justify-content-center" style="width:42px;height:42px;">
                            <i class="bi bi-graph-up-arrow"></i>
                        </div>
                    </div>
                    @if(!is_null($incomeChange))
                        <div class="small mt-2 {{ $incomeChange >= 0 ? 'text-success' : 'text-danger' }}">
                            <i class="bi {{ $incomeChange >= 0 ? 'bi-arrow-up-short' : 'bi-arrow-down-short' }}"></i>
                            {{ abs($incomeChange) }}% vs last month
                        </div>
                    @else
                        <div class="small mt-2 text-muted">No prior month to compare</div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="text-muted small mb-1">Total Expenditure</div>
                            <div class="fs-4 fw-bold text-danger">{{ number_format($expenseTotal, 2) }}</div>
                        </div>
                        <div class="rounded-circle bg-danger bg-opacity-10 text-danger d-flex align-items-center justify-content-center" style="width:42px;height:42px;">
                            <i class="bi bi-graph-down-arrow"></i>
                        </div>
                    </div>
                    @if(!is_null($expenseChange))
                        <div class="small mt-2 {{ $expenseChange <= 0 ? 'text-success' : 'text-danger' }}">
                            <i class="bi {{ $expenseChange <= 0 ? 'bi-arrow-down-short' : 'bi-arrow-up-short' }}"></i>
                            {{ abs($expenseChange) }}% vs last month
                        </div>
                    @else
                        <div class="small mt-2 text-muted">No prior month to compare</div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="text-muted small mb-1">Net Position</div>
                            <div class="fs-4 fw-bold {{ ($incomeTotal - $expenseTotal) >= 0 ? 'text-success' : 'text-danger' }}">
                                {{ number_format($incomeTotal - $expenseTotal, 2) }}
                            </div>
                        </div>
                        <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center" style="width:42px;height:42px;">
                            <i class="bi bi-wallet2"></i>
                        </div>
                    </div>
                    <div class="small mt-2 text-muted">
                        {{ ($incomeTotal - $expenseTotal) >= 0 ? 'Surplus' : 'Deficit' }} for {{ $academicYear }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Top performers --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small mb-1"><i class="bi bi-trophy me-1"></i>Top Income Category</div>
                    @if($topIncomeCategory)
                        <div class="fw-semibold">{{ $topIncomeCategory->name }}</div>
                        <div class="text-success small">{{ number_format($topIncomeCategory->total, 2) }}</div>
                    @else
                        <div class="text-muted small">No data yet</div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small mb-1"><i class="bi bi-person-check me-1"></i>Top Income Source</div>
                    @if($topIncomeSource)
                        <div class="fw-semibold">{{ $topIncomeSource->received_from }}</div>
                        <div class="text-success small">{{ number_format($topIncomeSource->total, 2) }}</div>
                    @else
                        <div class="text-muted small">No data yet</div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small mb-1"><i class="bi bi-tags me-1"></i>Top Expense Category</div>
                    @if($topExpenseCategory)
                        <div class="fw-semibold">{{ $topExpenseCategory->name }}</div>
                        <div class="text-danger small">{{ number_format($topExpenseCategory->total, 2) }}</div>
                    @else
                        <div class="text-muted small">No data yet</div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small mb-1"><i class="bi bi-shop me-1"></i>Top Vendor</div>
                    @if($topVendor)
                        <div class="fw-semibold">{{ $topVendor->vendor }}</div>
                        <div class="text-danger small">{{ number_format($topVendor->total, 2) }}</div>
                    @else
                        <div class="text-muted small">No data yet</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Charts --}}
    <div class="row g-3 mb-4">
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white fw-semibold">Income vs Expenditure — Monthly Trend</div>
                <div class="card-body" style="height: 300px !important;">
                    <canvas id="trendChart" height="260"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white fw-semibold">Income by Category</div>
                <div class="card-body">
                    <canvas id="incomeCategoryChart" height="260"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-semibold">Expenditure by Category</div>
                <div class="card-body">
                    <canvas id="expenseCategoryChart" height="140"></canvas>
                </div>
            </div>
        </div>
    </div>

    {{-- Quick nav --}}
    <div class="d-flex flex-wrap gap-2 mb-3">
        <a href="{{ route('accounting.income.index') }}" class="btn btn-outline-success btn-sm">Income</a>
        <a href="{{ route('accounting.expense.index') }}" class="btn btn-outline-danger btn-sm">Expenses</a>
        <a href="{{ route('accounting.income-categories.index') }}" class="btn btn-outline-secondary btn-sm">Income Categories</a>
        <a href="{{ route('accounting.expense-categories.index') }}" class="btn btn-outline-secondary btn-sm">Expense Categories</a>
    </div>

    {{-- Recent transactions --}}
    <div class="row g-3">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <span class="fw-semibold">Recent Income</span>
                    <a href="{{ route('accounting.income.index') }}" class="small">View all</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm mb-0 align-middle">
                        <thead class="table-light">
                        <tr><th>Date</th><th>Received From</th><th class="text-end">Amount</th></tr>
                        </thead>
                        <tbody>
                        @forelse($recentIncome as $t)
                            <tr>
                                <td>{{ $t->transaction_date->format('d M Y') }}</td>
                                <td>{{ $t->received_from ?: '—' }}</td>
                                <td class="text-end text-success fw-semibold">{{ number_format($t->total_amount, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center text-muted py-3">No income recorded yet</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <span class="fw-semibold">Recent Expenses</span>
                    <a href="{{ route('accounting.expense.index') }}" class="small">View all</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm mb-0 align-middle">
                        <thead class="table-light">
                        <tr><th>Date</th><th>Vendor</th><th class="text-end">Amount</th></tr>
                        </thead>
                        <tbody>
                        @forelse($recentExpenses as $t)
                            <tr>
                                <td>{{ $t->transaction_date->format('d M Y') }}</td>
                                <td>{{ $t->vendor ?: '—' }}</td>
                                <td class="text-end text-danger fw-semibold">{{ number_format($t->total_amount, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center text-muted py-3">No expenses recorded yet</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
    @endpush

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            new Chart(document.getElementById('trendChart'), {
                type: 'bar',
                data: {
                    labels: @json($monthlyLabels),
                    datasets: [
                        { label: 'Income', data: @json($monthlyIncomeData), backgroundColor: 'rgba(25,135,84,0.75)', borderRadius: 4 },
                        { label: 'Expenditure', data: @json($monthlyExpenseData), backgroundColor: 'rgba(220,53,69,0.75)', borderRadius: 4 }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { position: 'bottom' } },
                    scales: { y: { beginAtZero: true, ticks: { callback: v => v.toLocaleString() } } }
                }
            });

            new Chart(document.getElementById('incomeCategoryChart'), {
                type: 'doughnut',
                data: {
                    labels: @json($incomeByCategory->pluck('name')),
                    datasets: [{
                        data: @json($incomeByCategory->pluck('total')),
                        backgroundColor: ['#198754','#20c997','#0dcaf0','#6f42c1','#fd7e14','#ffc107','#0d6efd','#6610f2']
                    }]
                },
                options: { responsive: true, maintainAspectRatio: false,  plugins: { legend: { position: 'bottom' } } }
            });

            new Chart(document.getElementById('expenseCategoryChart'), {
                type: 'bar',
                data: {
                    labels: @json($expenseByCategory->pluck('name')),
                    datasets: [{ label: 'Expenditure', data: @json($expenseByCategory->pluck('total')), backgroundColor: 'rgba(220,53,69,0.75)', borderRadius: 4 }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: { x: { beginAtZero: true } }
                }
            });
        });
    </script>
@endsection
