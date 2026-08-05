@extends('layouts.app')
@section('title', 'New Fee Structure')

@section('content')
    <div class="mb-3">
        <h1 class="h4 mb-1">New Fee Structure</h1>
        <p class="text-muted mb-0">Add every votehead that applies, with its amount. Selecting more than one grade level creates a separate draft for each. Created as a draft — publish it once you're confident in the numbers.</p>
    </div>

    <form method="POST" action="{{ route('finance.fee-structures.store') }}">
        @csrf
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">Grade Level(s) <span class="text-danger">*</span></label>
                        <select name="grade_level_ids[]" class="form-select select2-field @error('grade_level_ids') is-invalid @enderror" multiple required>
                            @foreach($gradeLevels as $grade)
                                <option value="{{ $grade->id }}" @selected(in_array($grade->id, old('grade_level_ids', [])))>{{ $grade->name }}</option>
                            @endforeach
                        </select>
                        @error('grade_level_ids')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        @error('grade_level_ids.*')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" rows="2" class="form-control">{{ old('notes') }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="text-uppercase text-muted small mb-0">Fee Items</h6>
                    <button type="button" id="addItemBtn" class="btn btn-sm btn-outline-primary"><i class="bi bi-plus-lg me-1"></i> Add Item</button>
                </div>

                <div id="itemsContainer"></div>

                <template id="itemRowTemplate">
                    <div class="row g-2 align-items-start mb-2 item-row">
                        <div class="col-md-6">
                            <select name="items[__INDEX__][votehead_id]" class="form-select form-select-sm" required>
                                <option value="">Select votehead</option>
                                @foreach($voteheads as $votehead)
                                    <option value="{{ $votehead->id }}">{{ $votehead->name }} ({{ ucfirst($votehead->category) }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text">KES</span>
                                <input type="number" step="0.01" name="items[__INDEX__][amount]" class="form-control" placeholder="Amount" required>
                            </div>
                        </div>
                        <div class="col-md-2"><button type="button" class="btn btn-sm btn-outline-danger remove-item"><i class="bi bi-trash"></i></button></div>
                    </div>
                </template>

                <div id="totalDisplay" class="text-end fw-semibold mt-2">Total: KES 0.00</div>
                @error('items')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>
            <div class="card-footer bg-white border-0 p-4 pt-0 d-flex justify-content-end gap-2">
                <a href="{{ route('finance.fee-structures.index') }}" class="btn btn-sm btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-sm btn-primary px-4">Save as Draft</button>
            </div>
        </div>
    </form>
@endsection
@push('scripts')
    <script>
        $('.select2-field').select2({ theme: 'bootstrap-5', width: '100%', placeholder: 'Select grade level(s)' });

        const container = document.getElementById('itemsContainer');
        const template = document.getElementById('itemRowTemplate');
        let index = 0;

        function updateTotal() {
            let total = 0;
            container.querySelectorAll('input[name*="[amount]"]').forEach(input => total += parseFloat(input.value) || 0);
            document.getElementById('totalDisplay').textContent = 'Total: KES ' + total.toLocaleString(undefined, { minimumFractionDigits: 2 });
        }

        function addRow() {
            container.insertAdjacentHTML('beforeend', template.innerHTML.replaceAll('__INDEX__', index));
            index++;
        }

        document.getElementById('addItemBtn').addEventListener('click', addRow);
        container.addEventListener('input', updateTotal);
        container.addEventListener('click', e => {
            if (e.target.closest('.remove-item')) { e.target.closest('.item-row').remove(); updateTotal(); }
        });

        addRow();
    </script>
@endpush
