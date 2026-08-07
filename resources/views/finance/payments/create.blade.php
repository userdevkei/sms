@extends('layouts.app')
@section('title', 'Record Payment')

@section('content')
    <div class="mb-3"><h1 class="h4 mb-1">Record Payment</h1></div>

    <div class="card border-0 shadow-sm" style="max-width: 600px;">
        <div class="card-body">
            <form method="POST" action="{{ route('finance.payments.store') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label">Invoice <span class="text-danger">*</span></label>
                    @if($invoice)
                        <input type="hidden" name="invoice_id" value="{{ $invoice->id }}">
                        <div class="form-control-plaintext border rounded px-3 py-2 bg-light">
                            {{ $invoice->invoice_number }} - {{ $invoice->student->full_name }}
                            <span class="text-muted">(Balance: KES {{ number_format($invoice->balance, 2) }})</span>
                        </div>
                    @else
                        <input type="text" name="invoice_id" class="form-control @error('invoice_id') is-invalid @enderror" placeholder="Enter invoice ID" value="{{ old('invoice_id') }}" required>
                        @error('invoice_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text">Find an invoice from the Invoices list first, then click "Record Payment" there.</div>
                    @endif
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Payment Method <span class="text-danger">*</span></label>
                        <select name="method" id="methodSelect" class="form-select @error('method') is-invalid @enderror" required>
                            <option value="cash">Cash</option>
                            <option value="mpesa">M-Pesa</option>
                            <option value="bank">Bank Deposit</option>
                        </select>
                        @error('method')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Amount (KES) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="amount" class="form-control @error('amount') is-invalid @enderror" value="{{ old('amount') }}" required>
                        @error('amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6" id="referenceField">
                        <label class="form-label">Reference No. <span class="text-danger" id="refRequired">*</span></label>
                        <input type="text" name="reference_number" class="form-control @error('reference_number') is-invalid @enderror" value="{{ old('reference_number') }}" placeholder="M-Pesa code / deposit slip no.">
                        @error('reference_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Date Paid <span class="text-danger">*</span></label>
                        <input type="date" name="paid_on" class="form-control @error('paid_on') is-invalid @enderror" value="{{ old('paid_on', date('Y-m-d')) }}" required>
                        @error('paid_on')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" rows="2" class="form-control"></textarea>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-3">
                    <a href="{{ url()->previous() }}" class="btn btn-sm btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-sm btn-primary px-4">Record Payment</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const methodSelect = document.getElementById('methodSelect');
        const refRequired = document.getElementById('refRequired');
        function toggleRefRequired() { refRequired.style.display = methodSelect.value === 'cash' ? 'none' : 'inline'; }
        methodSelect.addEventListener('change', toggleRefRequired);
        toggleRefRequired();
    </script>
@endpush
