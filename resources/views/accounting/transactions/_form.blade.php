{{-- Included by income/create, income/edit, expenses/create, expenses/edit.
     Expects: $type ('income'|'expense'), $title, $action, $method, $transaction (null on create),
              $categories, $partyLabel ('Received From'|'Vendor'), $partyField ('received_from'|'vendor'),
              $referenceLabel ('Receipt No.'|'LPO / Voucher No.') --}}
<h1 class="h4 mb-3">{{ $title }}</h1>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="POST" action="{{ $action }}" id="transactionForm">
            @csrf
            @if($method === 'PUT') @method('PUT') @endif

            <div class="row g-3 mb-3">
                <div class="col-md-3">
                    <label class="form-label">{{ $referenceLabel }}</label>
                    <input type="text" name="reference" class="form-control"
                           value="{{ old('reference', $transaction->reference ?? '') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Date</label>
                    <input type="date" name="transaction_date" class="form-control @error('transaction_date') is-invalid @enderror"
                           value="{{ old('transaction_date', isset($transaction) ? $transaction->transaction_date->format('Y-m-d') : now()->format('Y-m-d')) }}" required>
                    @error('transaction_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">Academic Year</label>
                    <input type="text" name="academic_year" class="form-control @error('academic_year') is-invalid @enderror"
                           value="{{ old('academic_year', $transaction->academic_year ?? now()->year) }}" required>
                    @error('academic_year') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">Term</label>
                    <select name="term" class="form-select @error('term') is-invalid @enderror" required>
                        @foreach([1,2,3] as $t)
                            <option value="{{ $t }}" @selected(old('term', $transaction->term ?? '') == $t)>Term {{ $t }}</option>
                        @endforeach
                    </select>
                    @error('term') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <label class="form-label">{{ $partyLabel }}</label>
                    <input type="text" name="{{ $partyField }}" class="form-control"
                           value="{{ old($partyField, $transaction->$partyField ?? '') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Payment Method</label>
                    <select name="payment_method" class="form-select">
                        <option value="">—</option>
                        @foreach(['cash' => 'Cash', 'mpesa' => 'M-Pesa', 'bank' => 'Bank', 'cheque' => 'Cheque'] as $val => $label)
                            <option value="{{ $val }}" @selected(old('payment_method', $transaction->payment_method ?? '') === $val)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Notes</label>
                    <input type="text" name="description" class="form-control"
                           value="{{ old('description', $transaction->description ?? '') }}">
                </div>
            </div>

            <hr>

            <div class="d-flex justify-content-between align-items-center mb-2">
                <h2 class="h6 mb-0">Items</h2>
                <button type="button" id="addItemRow" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-plus-lg me-1"></i> Add Item
                </button>
            </div>
            @error('items') <div class="text-danger small mb-2">{{ $message }}</div> @enderror

            <div class="table-responsive">
                <table class="table table-sm align-middle" id="itemsTable">
                    <thead>
                    <tr>
                        <th style="min-width:180px">Category</th>
                        <th>Description</th>
                        <th style="width:100px">Qty</th>
                        <th style="width:130px">Unit Price</th>
                        <th style="width:130px">Amount</th>
                        <th style="width:40px"></th>
                    </tr>
                    </thead>
                    <tbody id="itemsBody"></tbody>
                    <tfoot>
                    <tr>
                        <td colspan="4" class="text-end fw-bold">Total</td>
                        <td class="fw-bold" id="itemsTotal">0.00</td>
                        <td></td>
                    </tr>
                    </tfoot>
                </table>
            </div>

            <div class="d-flex justify-content-between mt-3">
                <a href="{{ route("accounting.{$type}.index") }}" class="btn btn-sm btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-sm btn-primary">Save Transaction</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
    @php
        $categoriesData = $categories->map(fn($c) => ['id' => $c->id, 'name' => $c->name]);

        // Existing items on edit, or old() input on a failed validation redirect.
        $existingItemsData = old('items') ?? (isset($transaction)
            ? $transaction->items->map(fn($i) => [
                'category_id' => $i->{$type . '_category_id'},
                'description' => $i->description,
                'quantity' => (string) $i->quantity,
                'unit_price' => (string) $i->unit_price,
            ])
            : []);
    @endphp
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const categories = @json($categoriesData);
            const existingItems = @json($existingItemsData);

            const body = document.getElementById('itemsBody');
            let rowIndex = 0;

            function categoryOptions(selected) {
                return categories.map(c =>
                    `<option value="${c.id}" ${c.id === selected ? 'selected' : ''}>${c.name}</option>`
                ).join('');
            }

            function addRow(item = {}) {
                const i = rowIndex++;
                const tr = document.createElement('tr');
                tr.innerHTML = `
            <td>
                <select name="items[${i}][category_id]" class="form-select form-select-sm" required>
                    <option value="">Select…</option>
                    ${categoryOptions(item.category_id || '')}
                </select>
            </td>
            <td><input type="text" name="items[${i}][description]" class="form-control form-control-sm" value="${item.description || ''}" required></td>
            <td><input type="number" step="0.01" min="0.01" name="items[${i}][quantity]" class="form-control form-control-sm item-qty" value="${item.quantity || 1}" required></td>
            <td><input type="number" step="0.01" min="0" name="items[${i}][unit_price]" class="form-control form-control-sm item-price" value="${item.unit_price || ''}" required></td>
            <td class="item-amount text-end">0.00</td>
            <td><button type="button" class="btn btn-sm btn-outline-danger remove-row"><i class="bi bi-x"></i></button></td>
        `;
                body.appendChild(tr);
                recalcRow(tr);
            }

            function recalcRow(tr) {
                const qty = parseFloat(tr.querySelector('.item-qty').value) || 0;
                const price = parseFloat(tr.querySelector('.item-price').value) || 0;
                tr.querySelector('.item-amount').textContent = (qty * price).toFixed(2);
                recalcTotal();
            }

            function recalcTotal() {
                let total = 0;
                document.querySelectorAll('.item-amount').forEach(td => total += parseFloat(td.textContent) || 0);
                document.getElementById('itemsTotal').textContent = total.toFixed(2);
            }

            body.addEventListener('input', function (e) {
                if (e.target.classList.contains('item-qty') || e.target.classList.contains('item-price')) {
                    recalcRow(e.target.closest('tr'));
                }
            });

            body.addEventListener('click', function (e) {
                if (e.target.closest('.remove-row')) {
                    e.target.closest('tr').remove();
                    recalcTotal();
                }
            });

            document.getElementById('addItemRow').addEventListener('click', () => addRow());

            if (existingItems.length) {
                existingItems.forEach(addRow);
            } else {
                addRow();
            }
        });
    </script>
@endpush
