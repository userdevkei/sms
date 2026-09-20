@extends('layouts.app')
@section('title', 'Bulk Add Time Slots')

@push('styles')
    <style>
        .bulk-row { display: grid; grid-template-columns: 90px 90px 130px 1fr 90px 70px 40px; gap: 0.6rem; align-items: center; margin-bottom: 0.5rem; }
        .bulk-row input, .bulk-row select { font-size: 0.85rem; padding: 0.3rem 0.5rem; }
        .bulk-row .row-num { font-size: 0.8rem; color: #7A8391; text-align: center; }
        .bulk-row.is-break { background: #FEF6E7; border-radius: 4px; padding: 0.25rem; margin: 0.25rem 0; }
        .bulk-header { display: grid; grid-template-columns: 90px 90px 130px 1fr 90px 70px 40px; gap: 0.6rem; font-size: 0.72rem; color: #7A8391; margin-bottom: 0.4rem; padding: 0 0.1rem; }
        .day-check-grid { display: flex; gap: 1.25rem; flex-wrap: wrap; }
        .quick-fill { background: #F6F5F1; border: 1px solid #E4E1E1; border-left: 3px solid #122744; padding: 0.9rem 1.1rem; margin-bottom: 1.25rem; display: flex; align-items: flex-end; gap: 1.25rem; flex-wrap: wrap; }
        .quick-fill .field label { font-size: 0.72rem; color: #7A8391; display: block; margin-bottom: 0.2rem; }
        .break-check { text-align: center; }
    </style>
@endpush

@section('content')
    <h1 class="h4 mb-1">Bulk Add Time Slots</h1>
    <p class="text-muted small mb-3">Group: <strong>{{ $group->name }}</strong></p>

    <form method="POST" action="{{ route('timeslots.bulk-store') }}" id="bulkForm">
        @csrf
        <input type="hidden" name="time_slot_group_id" value="{{ $group->id }}">

        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body">
                <label class="form-label">Apply to Day(s)</label>
                <div class="day-check-grid mb-2">
                    @foreach(\App\Models\TimeSlot::DAYS as $num => $name)
                        <div class="form-check">
                            <input type="checkbox" name="days[]" value="{{ $num }}" class="form-check-input" id="day-{{ $num }}"
                                @checked($num <= 5)>
                            <label class="form-check-label" for="day-{{ $num }}">{{ $name }}</label>
                        </div>
                    @endforeach
                </div>
                <div class="form-check">
                    <input type="checkbox" name="skip_existing" value="1" class="form-check-input" id="skipExisting" checked>
                    <label class="form-check-label" for="skipExisting">Skip rows that would duplicate an existing slot in this group (same day + exact time range)</label>
                </div>
            </div>
        </div>

        <div class="quick-fill">
            <div class="field">
                <label>Day starts at</label>
                <input type="time" id="quickStart" value="08:00" class="form-control form-control-sm">
            </div>
            <div class="field">
                <label>Period length (min)</label>
                <input type="number" id="quickDuration" value="40" min="5" class="form-control form-control-sm" style="width:90px">
            </div>
            <div class="field">
                <label>Number of periods</label>
                <input type="number" id="quickCount" value="8" min="1" max="15" class="form-control form-control-sm" style="width:90px">
            </div>
            <div class="field">
                <label>Break every ___ periods</label>
                <input type="number" id="quickBreakEvery" value="0" min="0" max="15" class="form-control form-control-sm" style="width:90px" title="0 = no automatic breaks">
            </div>
            <div class="field">
                <label>Break length (min)</label>
                <input type="number" id="quickBreakDuration" value="20" min="5" class="form-control form-control-sm" style="width:90px">
            </div>
            <button type="button" id="quickGenerate" class="btn btn-sm btn-outline-primary">Generate rows</button>
        </div>
        <p class="text-muted small mb-3">Generates the table below — you can still edit any time, add/remove rows, or flip the Break checkbox on any row afterward.</p>

        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="bulk-header">
                    <span>#</span><span>Start</span><span>End</span><span>Label</span><span>Type</span><span class="break-check">Break</span><span></span>
                </div>
                <div id="rowsContainer"></div>
                <div class="d-flex gap-2 mt-2">
                    <button type="button" id="addRow" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-plus-lg me-1"></i> Add Row
                    </button>
                    <button type="button" id="addBreak" class="btn btn-sm btn-outline-warning">
                        <i class="bi bi-cup-hot me-1"></i> Add Break
                    </button>
                </div>
            </div>
            <div class="card-footer bg-white border-0 d-flex justify-content-between">
                <a href="{{ route('timeslots.index', ['group' => $group->id]) }}" class="btn btn-sm btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-sm btn-primary">Save All Slots</button>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const container = document.getElementById('rowsContainer');
            let rowIndex = 0;

            function minutesToTime(total) {
                const h = Math.floor(total / 60) % 24;
                const m = total % 60;
                return String(h).padStart(2, '0') + ':' + String(m).padStart(2, '0');
            }

            function addRow(start = '', end = '', label = '', type = 'main', isBreak = false) {
                const i = rowIndex++;
                const row = document.createElement('div');
                row.className = 'bulk-row' + (isBreak ? ' is-break' : '');
                row.innerHTML = `
            <div class="row-num">${container.children.length + 1}</div>
            <input type="time" name="rows[${i}][start_time]" class="form-control form-control-sm" value="${start}" required>
            <input type="time" name="rows[${i}][end_time]" class="form-control form-control-sm" value="${end}" required>
            <input type="text" name="rows[${i}][label]" class="form-control form-control-sm" placeholder="e.g. Period ${container.children.length + 1}" value="${label}">
            <select name="rows[${i}][type]" class="form-select form-select-sm row-type" ${isBreak ? 'disabled' : ''}>
                <option value="main" ${type === 'main' ? 'selected' : ''}>Main</option>
                <option value="remedial" ${type === 'remedial' ? 'selected' : ''}>Remedial</option>
            </select>
            <div class="break-check">
                <input type="checkbox" class="form-check-input row-break-toggle" ${isBreak ? 'checked' : ''}>
                <input type="hidden" name="rows[${i}][is_break]" value="${isBreak ? 1 : 0}" class="row-break-value">
            </div>
            <button type="button" class="btn btn-sm btn-outline-danger remove-row"><i class="bi bi-x"></i></button>
        `;
                container.appendChild(row);
                renumber();
            }

            function renumber() {
                [...container.children].forEach((row, idx) => {
                    row.querySelector('.row-num').textContent = idx + 1;
                });
            }

            // Any row's Break checkbox can be toggled at any time — flips the hidden
            // value the server reads, disables the Type select, and tints the row.
            container.addEventListener('change', function (e) {
                if (e.target.classList.contains('row-break-toggle')) {
                    const row = e.target.closest('.bulk-row');
                    const checked = e.target.checked;
                    row.classList.toggle('is-break', checked);
                    row.querySelector('.row-break-value').value = checked ? 1 : 0;
                    row.querySelector('.row-type').disabled = checked;
                }
            });

            container.addEventListener('click', function (e) {
                if (e.target.closest('.remove-row')) {
                    e.target.closest('.bulk-row').remove();
                    renumber();
                }
            });

            document.getElementById('addRow').addEventListener('click', () => addRow());
            document.getElementById('addBreak').addEventListener('click', () => addRow('', '', 'Break', 'main', true));

            document.getElementById('quickGenerate').addEventListener('click', function () {
                container.innerHTML = '';
                rowIndex = 0;

                const [h, m] = document.getElementById('quickStart').value.split(':').map(Number);
                const duration = parseInt(document.getElementById('quickDuration').value, 10) || 40;
                const count = parseInt(document.getElementById('quickCount').value, 10) || 8;
                const breakEvery = parseInt(document.getElementById('quickBreakEvery').value, 10) || 0;
                const breakDuration = parseInt(document.getElementById('quickBreakDuration').value, 10) || 20;

                const breakOrdinals = ['First', 'Second', 'Third', 'Fourth', 'Fifth', 'Sixth'];
                let breakNumber = 0;
                let cursorMinutes = h * 60 + m;
                let periodsSinceBreak = 0;

                for (let p = 1; p <= count; p++) {
                    const startStr = minutesToTime(cursorMinutes);
                    cursorMinutes += duration;
                    const endStr = minutesToTime(cursorMinutes);
                    addRow(startStr, endStr, `Period ${p}`);
                    periodsSinceBreak++;

                    const isLastPeriod = p === count;
                    if (breakEvery > 0 && periodsSinceBreak >= breakEvery && !isLastPeriod) {
                        const bStart = minutesToTime(cursorMinutes);
                        cursorMinutes += breakDuration;
                        const bEnd = minutesToTime(cursorMinutes);
                        const label = (breakOrdinals[breakNumber] || (breakNumber + 1) + 'th') + ' Break';
                        addRow(bStart, bEnd, label, 'main', true);
                        breakNumber++;
                        periodsSinceBreak = 0;
                    }
                }
            });

            addRow();
        });
    </script>
@endpush
