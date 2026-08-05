{{-- resources/views/finance/statement/show.blade.php --}}
@extends('layouts.app')
@section('title', 'Student Statement')

@section('content')
    <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
            <h1 class="h4 mb-1">Student Statement</h1>
            <p class="text-muted mb-0">Full running account of invoices and payments.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('finance.statement.pdf') }}" target="_blank" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-download me-1"></i> Download PDF
            </a>
            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#payModal">
                <i class="bi bi-phone me-1"></i> Pay via M-Pesa
            </button>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body d-flex justify-content-between align-items-center">
            <span class="text-muted">Current Balance</span>
            <span class="h5 mb-0 {{ $closingBalance > 0 ? 'text-danger' : 'text-success' }}">
                KES {{ number_format($closingBalance, 2) }}
            </span>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table id="statementTable" class="table table-sm align-middle w-100">
                    <thead>
                    <tr><th>Date</th><th>Description</th><th class="text-end">Debit</th><th class="text-end">Credit</th><th class="text-end">Balance</th></tr>
                    </thead>
                    <tbody>
                    @forelse($lines as $line)
                        <tr>
                            <td data-order="{{ $line['date']->format('Y-m-d') }}">{{ $line['date']->format('d M Y') }}</td>
                            <td>{{ $line['description'] }}</td>
                            <td class="text-end">{{ $line['debit'] > 0 ? number_format($line['debit'], 2) : '' }}</td>
                            <td class="text-end text-success">{{ $line['credit'] > 0 ? number_format($line['credit'], 2) : '' }}</td>
                            <td class="text-end fw-semibold">{{ number_format($line['balance'], 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-3">No transactions yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Pay Modal --}}
    <div class="modal fade" id="payModal" tabindex="-1">
        <div class="modal-dialog">
            <form id="payForm" class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Pay via M-Pesa</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="payAlert" class="alert d-none" role="alert"></div>
                    <div class="mb-3">
                        <label class="form-label">Amount (KES) <span class="text-danger">*</span></label>
                        <input type="number" min="1" step="0.01" id="pay_amount" class="form-control" required>
                    </div>
                    <div class="mb-0">
                        <label class="form-label">M-Pesa Phone Number <span class="text-danger">*</span></label>
                        <input type="text" id="pay_phone" class="form-control" placeholder="07XXXXXXXX" required>
                        <small class="text-muted">You'll receive an STK push prompt on this number.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="payBtn">
                        <i class="bi bi-phone me-1"></i> Send Payment Request
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('styles')<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">@endpush
@push('scripts')
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $('#statementTable').DataTable({ order: [[0, 'asc']], pageLength: 25 });

        document.getElementById('payForm').addEventListener('submit', function (e) {
            e.preventDefault();

            const btn = document.getElementById('payBtn');
            const alertBox = document.getElementById('payAlert');
            alertBox.className = 'alert d-none';

            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Sending...';

            fetch(@json(route('finance.statement.pay')), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': @json(csrf_token()),
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    amount: document.getElementById('pay_amount').value,
                    phone: document.getElementById('pay_phone').value,
                }),
            })
                .then(r => r.json())
                .then(res => {
                    alertBox.textContent = res.message;
                    alertBox.className = 'alert ' + (res.success ? 'alert-success' : 'alert-danger');
                    btn.disabled = false;
                    btn.innerHTML = '<i class="bi bi-phone me-1"></i> Send Payment Request';
                })
                .catch(() => {
                    alertBox.textContent = 'Something went wrong. Please try again.';
                    alertBox.className = 'alert alert-danger';
                    btn.disabled = false;
                    btn.innerHTML = '<i class="bi bi-phone me-1"></i> Send Payment Request';
                });
        });
    </script>
@endpush
