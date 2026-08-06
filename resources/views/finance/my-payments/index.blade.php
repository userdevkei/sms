@extends('layouts.app')
@section('title', 'My Payments')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 mb-1">My Payments</h1>
            <p class="text-muted mb-0">M-Pesa payment history and status.</p>
        </div>
        <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#payModal">
            <i class="bi bi-phone me-1"></i> Pay via M-Pesa
        </button>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table id="paymentsTable" class="table table-sm align-middle w-100">
                    <thead>
                    <tr>
                        <th>#</th>
                        <th>Date</th>
                        <th>Phone</th>
                        <th class="text-end">Amount (KES)</th>
                        <th>Status</th>
                        <th>M-Pesa Ref</th>
                        <th>Details</th>
                        <th class="text-end">Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($transactions as $t)
                        <tr id="txn-row-{{ $t->id }}">
                            <td>{{ $loop->iteration }}</td>
                            <td class="text-nowrap">{{ $t->created_at->format('d M Y H:i') }}</td>
                            <td>{{ $t->phone_number }}</td>
                            <td class="text-end">{{ number_format($t->amount, 2) }}</td>
                            <td>
                                @php
                                    $badge = match($t->status) {
                                        'success'   => 'bg-success',
                                        'pending'   => 'bg-warning text-dark',
                                        'failed'    => 'bg-danger',
                                        'cancelled' => 'bg-secondary',
                                        default     => 'bg-secondary',
                                    };
                                @endphp
                                <span class="badge {{ $badge }} txn-status" id="status-{{ $t->id }}">
                                    {{ ucfirst($t->status) }}
                                </span>
                            </td>
                            <td class="text-muted small">{{ $t->payment_id ?? '—' }}</td>
                            <td class="text-muted small">{{ $t->result_description ?? '—' }}</td>
                            <td nowrap="" class="text-end">
                                @if(in_array($t->status, ['pending', 'failed', 'cancelled']))
                                    <button class="btn btn-sm btn-outline-primary retry-btn"
                                            data-id="{{ $t->id }}"
                                            data-phone="{{ $t->phone_number }}"
                                            data-amount="{{ $t->amount }}"
                                            data-bs-toggle="modal" data-bs-target="#retryModal">
                                        <i class="bi bi-arrow-clockwise"></i> Retry
                                    </button>
                                @endif
                                @if($t->status === 'pending')
                                    <button class="btn btn-sm btn-outline-success ms-1 check-status-btn"
                                            data-id="{{ $t->id }}"
                                            data-url="{{ route('finance.my-payments.status', $t->id) }}">
                                        <i class="bi bi-check2"></i> I've Paid
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Pay Modal --}}
    <div class="modal fade" id="payModal" tabindex="-1">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h5 class="modal-title"><i class="bi bi-phone me-1"></i> Pay via M-Pesa</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="payFormSection">
                        <div class="mb-3">
                            <label class="form-label">Phone Number</label>
                            <input type="tel" id="payPhone" class="form-control" value="{{ $user->phone_number }}" placeholder="07XXXXXXXX">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Amount (KES)</label>
                            <input type="number" id="payAmount" class="form-control" min="1" placeholder="e.g. 5000">
                        </div>
                        <div id="payAlert" class="alert d-none"></div>
                    </div>

                    <div id="payWaitSection" class="d-none text-center py-3">
                        <div class="spinner-border text-success mb-3" role="status"></div>
                        <p class="mb-1">Enter your M-Pesa PIN on your phone to complete payment.</p>
                        <p class="text-muted small mb-3" id="payWaitStatus">Fetching payment status…</p>
                        <button type="button" class="btn btn-sm btn-outline-success" id="payIvePaidBtn">
                            <i class="bi bi-check2"></i> I've Paid
                        </button>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-sm btn-success" id="paySubmitBtn">
                        <span id="payBtnText"><i class="bi bi-send me-1"></i> Send STK Push</span>
                        <span id="payBtnSpinner" class="spinner-border spinner-border-sm d-none"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Retry Modal --}}
    <div class="modal fade" id="retryModal" tabindex="-1">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h5 class="modal-title"><i class="bi bi-arrow-clockwise me-1"></i> Retry Payment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="retryFormSection">
                        <div class="mb-3">
                            <label class="form-label">Phone Number</label>
                            <input type="tel" id="retryPhone" class="form-control" placeholder="07XXXXXXXX">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Amount (KES)</label>
                            <input type="number" id="retryAmount" class="form-control" min="1">
                        </div>
                        <input type="hidden" id="retryTxnId">
                        <div id="retryAlert" class="alert d-none"></div>
                    </div>

                    <div id="retryWaitSection" class="d-none text-center py-3">
                        <div class="spinner-border text-success mb-3" role="status"></div>
                        <p class="mb-1">Enter your M-Pesa PIN on your phone to complete payment.</p>
                        <p class="text-muted small mb-3" id="retryWaitStatus">Fetching payment status…</p>
                        <button type="button" class="btn btn-sm btn-outline-success" id="retryIvePaidBtn">
                            <i class="bi bi-check2"></i> I've Paid
                        </button>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-sm btn-primary" id="retrySubmitBtn">
                        <span id="retryBtnText"><i class="bi bi-send me-1"></i> Retry</span>
                        <span id="retryBtnSpinner" class="spinner-border spinner-border-sm d-none"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">@endpush
@push('scripts')
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
    <script>
        const paymentsTable = $('#paymentsTable').DataTable({
            order: [[1, 'desc']],
            pageLength: 25,
            columnDefs: [
                { targets: [0], orderable: false },
                { targets: [-1], orderable: false },
            ],
        });

        const CSRF = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const STATUS_URL_BASE = @json(url('finance/my-payments/status'));

        function setLoading(btnText, btnSpinner, loading) {
            document.getElementById(btnText).classList.toggle('d-none', loading);
            document.getElementById(btnSpinner).classList.toggle('d-none', !loading);
        }

        function showAlert(id, type, msg) {
            const el = document.getElementById(id);
            el.className = `alert alert-${type}`;
            el.textContent = msg;
        }

        // ─────────────────────────────────────────────
        // Row-level auto polling (table)
        // Each transaction id has its own interval keyed
        // in rowPolls; starting/stopping one id never
        // touches any other id's interval or row.
        // ─────────────────────────────────────────────
        const rowPolls = {}; // id -> intervalId

        function statusBadgeClass(status) {
            return { success: 'bg-success', pending: 'bg-warning text-dark', failed: 'bg-danger', cancelled: 'bg-secondary' }[status] ?? 'bg-secondary';
        }

        // Updates only the one row that changed — no reload, no touching other rows/polls.
        function updateTransactionRow(id, data) {
            if (!id || !data) return;

            const badge = document.getElementById(`status-${id}`);
            if (badge) {
                badge.className = `badge txn-status ${statusBadgeClass(data.status)}`;
                badge.textContent = data.status.charAt(0).toUpperCase() + data.status.slice(1);
            }

            const row = document.getElementById(`txn-row-${id}`);
            if (!row) return;

            const refCell = row.children[5];
            const detailsCell = row.children[6];
            const actionCell = row.children[7];
            if (refCell) refCell.textContent = data.payment_id || '—';
            if (detailsCell) detailsCell.textContent = data.result_description || '—';

            if (actionCell) {
                actionCell.innerHTML = '';
                if (['pending', 'failed', 'cancelled'].includes(data.status)) {
                    const retryBtn = document.createElement('button');
                    retryBtn.className = 'btn btn-sm btn-outline-primary retry-btn';
                    retryBtn.dataset.id = id;
                    retryBtn.dataset.phone = row.children[2].textContent.trim();
                    retryBtn.dataset.amount = row.children[3].textContent.trim().replace(/,/g, '');
                    retryBtn.setAttribute('data-bs-toggle', 'modal');
                    retryBtn.setAttribute('data-bs-target', '#retryModal');
                    retryBtn.innerHTML = '<i class="bi bi-arrow-clockwise"></i> Retry';
                    retryBtn.addEventListener('click', attachRetryPrefill);
                    actionCell.appendChild(retryBtn);
                }
                if (data.status === 'pending') {
                    const url = `${STATUS_URL_BASE}/${id}`;
                    const paidBtn = document.createElement('button');
                    paidBtn.className = 'btn btn-sm btn-outline-success ms-1 check-status-btn';
                    paidBtn.dataset.id = id;
                    paidBtn.dataset.url = url;
                    paidBtn.innerHTML = '<i class="bi bi-check2"></i> I\'ve Paid';
                    paidBtn.addEventListener('click', attachRowCheckHandler);
                    actionCell.appendChild(paidBtn);
                }
            }

            if (data.status !== 'pending') stopRowPoll(id);
        }

        // Checks ONLY the given id/url — never touches other transactions.
        function checkTransactionStatus(id, url) {
            return fetch(url).then(r => r.json()).then(d => {
                updateTransactionRow(id, d);
                return d.status;
            });
        }

        function startRowPoll(id, url, { interval = 6000, maxAttempts = 30 } = {}) {
            if (rowPolls[id]) return; // already polling this exact transaction, do nothing
            let attempts = 0;
            rowPolls[id] = setInterval(() => {
                if (document.hidden) return;
                attempts++;
                checkTransactionStatus(id, url);
                if (attempts >= maxAttempts) stopRowPoll(id);
            }, interval);
        }

        function stopRowPoll(id) {
            if (rowPolls[id]) {
                clearInterval(rowPolls[id]);
                delete rowPolls[id];
            }
        }

        // "I've Paid" click handler — scoped to the single id/url on the
        // clicked button via its own dataset, so it only ever checks
        // that one transaction, never the whole table.
        function attachRowCheckHandler() {
            const id = this.dataset.id;
            const url = this.dataset.url;
            this.disabled = true;
            const original = this.innerHTML;
            this.innerHTML = `<span class="spinner-border spinner-border-sm"></span> Checking…`;

            checkTransactionStatus(id, url).finally(() => {
                this.disabled = false;
                this.innerHTML = original;
            });
        }

        function attachRetryPrefill() {
            document.getElementById('retryTxnId').value  = this.dataset.id;
            document.getElementById('retryPhone').value  = this.dataset.phone;
            document.getElementById('retryAmount').value = this.dataset.amount;
            document.getElementById('retryAlert').className = 'alert d-none';
        }

        document.querySelectorAll('tr[id^="txn-row-"]').forEach(row => {
            const id = row.id.replace('txn-row-', '');
            const badge = row.querySelector('.txn-status');
            const btn = row.querySelector('.check-status-btn');
            if (badge && btn && badge.textContent.trim().toLowerCase() === 'pending') {
                startRowPoll(id, btn.dataset.url);
            }
        });

        document.querySelectorAll('.check-status-btn').forEach(btn => btn.addEventListener('click', attachRowCheckHandler));

        // ─────────────────────────────────────────────
        // Modal wait-state (Pay / Retry) — shared logic
        // Each modal poll is keyed by prefix ('pay' / 'retry')
        // and carries its own txnId, so it only ever checks
        // the transaction that modal just created.
        // ─────────────────────────────────────────────
        const modalPolls = {};

        function resetModalWaitState(prefix) {
            if (modalPolls[prefix]) {
                clearInterval(modalPolls[prefix]);
                delete modalPolls[prefix];
            }
            document.getElementById(`${prefix}FormSection`).classList.remove('d-none');
            document.getElementById(`${prefix}WaitSection`).classList.add('d-none');
            document.getElementById(`${prefix}SubmitBtn`).classList.remove('d-none');
        }

        // txnId lets us patch that one row in the table once the modal closes/succeeds —
        // this poll never fetches or touches any other transaction.
        function enterModalWaitState(prefix, statusUrl, txnId) {
            document.getElementById(`${prefix}FormSection`).classList.add('d-none');
            document.getElementById(`${prefix}SubmitBtn`).classList.add('d-none');
            document.getElementById(`${prefix}WaitSection`).classList.remove('d-none');

            const statusEl = document.getElementById(`${prefix}WaitStatus`);
            statusEl.textContent = 'Fetching payment status…';

            let attempts = 0;
            const maxAttempts = 20;

            const finish = (status, data) => {
                clearInterval(modalPolls[prefix]);
                delete modalPolls[prefix];

                if (data) updateTransactionRow(txnId, data); // patch just this row, no reload

                if (status === 'success') {
                    statusEl.textContent = 'Payment confirmed!';
                    // New transaction may not exist in the table yet (pay flow) — refresh
                    // the table via ajax so the row appears, without touching other polls.
                    if (!document.getElementById(`txn-row-${txnId}`)) {
                        paymentsTable.ajax?.reload ? paymentsTable.ajax.reload(null, false) : location.reload();
                    }
                } else if (status === 'failed' || status === 'cancelled') {
                    statusEl.textContent = 'Payment was not completed. You can retry from the table.';
                } else {
                    statusEl.textContent = 'Still pending — you can close this and check later.';
                }
                setTimeout(() => {
                    bootstrap.Modal.getInstance(document.getElementById(`${prefix}Modal`))?.hide();
                }, status === 'success' ? 1200 : 2000);
            };

            const check = () => {
                if (document.hidden) return;
                attempts++;
                fetch(statusUrl).then(r => r.json()).then(d => {
                    if (d.status !== 'pending') {
                        finish(d.status, d);
                    } else if (attempts >= maxAttempts) {
                        finish('timeout', null);
                    } else {
                        statusEl.textContent = 'Waiting for confirmation…';
                    }
                }).catch(() => {});
            };

            modalPolls[prefix] = setInterval(check, 4000);
            check();

            document.getElementById(`${prefix}IvePaidBtn`).onclick = function () {
                this.disabled = true;
                const original = this.innerHTML;
                this.innerHTML = `<span class="spinner-border spinner-border-sm"></span> Checking…`;
                fetch(statusUrl).then(r => r.json()).then(d => {
                    if (d.status !== 'pending') {
                        finish(d.status, d);
                    } else {
                        statusEl.textContent = 'Still waiting — payment not yet confirmed by M-Pesa.';
                    }
                }).finally(() => {
                    this.disabled = false;
                    this.innerHTML = original;
                });
            };
        }

        // ── Pay ──
        document.getElementById('paySubmitBtn').addEventListener('click', function () {
            const phone  = document.getElementById('payPhone').value.trim();
            const amount = document.getElementById('payAmount').value.trim();
            if (!phone || !amount) return showAlert('payAlert', 'warning', 'Please fill in all fields.');

            setLoading('payBtnText', 'payBtnSpinner', true);

            fetch('{{ route('finance.my-payments.initiate') }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
                body: JSON.stringify({ phone, amount }),
            })
                .then(r => r.json().then(d => ({ ok: r.ok, d })))
                .then(({ ok, d }) => {
                    setLoading('payBtnText', 'payBtnSpinner', false);
                    if (ok) {
                        enterModalWaitState('pay', d.status_url, d.transaction_id);
                    } else {
                        showAlert('payAlert', 'danger', d.message ?? 'Something went wrong.');
                    }
                })
                .catch(() => { setLoading('payBtnText', 'payBtnSpinner', false); showAlert('payAlert', 'danger', 'Network error. Try again.'); });
        });

        document.getElementById('payModal').addEventListener('hidden.bs.modal', () => resetModalWaitState('pay'));

        // ── Retry modal pre-fill ──
        document.querySelectorAll('.retry-btn').forEach(btn => {
            btn.addEventListener('click', attachRetryPrefill);
        });

        // ── Retry submit ──
        document.getElementById('retrySubmitBtn').addEventListener('click', function () {
            const id     = document.getElementById('retryTxnId').value;
            const phone  = document.getElementById('retryPhone').value.trim();
            const amount = document.getElementById('retryAmount').value.trim();
            if (!phone || !amount) return showAlert('retryAlert', 'warning', 'Please fill in all fields.');

            setLoading('retryBtnText', 'retryBtnSpinner', true);

            fetch(`/my-payments/retry/${id}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
                body: JSON.stringify({ phone, amount }),
            })
                .then(r => r.json().then(d => ({ ok: r.ok, d })))
                .then(({ ok, d }) => {
                    setLoading('retryBtnText', 'retryBtnSpinner', false);
                    if (ok) {
                        enterModalWaitState('retry', d.status_url, d.transaction_id);
                    } else {
                        showAlert('retryAlert', 'danger', d.message ?? 'Something went wrong.');
                    }
                })
                .catch(() => { setLoading('retryBtnText', 'retryBtnSpinner', false); showAlert('retryAlert', 'danger', 'Network error. Try again.'); });
        });

        document.getElementById('retryModal').addEventListener('hidden.bs.modal', () => resetModalWaitState('retry'));
    </script>
@endpush
