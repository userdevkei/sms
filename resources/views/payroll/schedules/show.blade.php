@extends('layouts.app')
@section('content')
    @php
        $draft = $schedule->isDraft();
        $kinds = \App\Models\PaymentScheduleLineItem::KINDS;
        $badge = ['draft' => 'secondary', 'approved' => 'info', 'paid' => 'success'][$schedule->status];
        $nonTax = fn ($l) => $l->non_taxable_allowances + $l->one_off_reimbursement;
        $ded    = fn ($l) => $l->pension + $l->other_deductions + $l->one_off_deduction;
    @endphp

    <style>
        .ps-table thead th { font-size:.7rem; text-transform:uppercase; letter-spacing:.05em; color:#6c757d; font-weight:600; white-space:nowrap; background:#f8f9fa; border-bottom:1px solid #dee2e6; }
        .ps-table td { font-size:.85rem; vertical-align:middle; }
        .ps-table .num { text-align:right; font-variant-numeric:tabular-nums; white-space:nowrap; }
        .ps-table tfoot td { font-weight:600; background:#f8f9fa; border-top:2px solid #dee2e6; }
        .ps-stat { border:0; box-shadow:0 1px 3px rgba(0,0,0,.08); }
        .ps-stat .lbl { font-size:.68rem; text-transform:uppercase; letter-spacing:.05em; color:#6c757d; }
        .ps-stat .val { font-size:1.05rem; font-weight:700; font-variant-numeric:tabular-nums; }
        .ps-days { width:60px; text-align:center; }
        .adj-head { font-size:.7rem; text-transform:uppercase; letter-spacing:.05em; color:#6c757d; font-weight:600; }
        .adj-legend div { margin-bottom:.25rem; }
    </style>

    <div class="container-fluid" id="psRoot"
         data-config="{{ json_encode(config('kenya_payroll')) }}"
         data-kinds="{{ json_encode($kinds) }}"
         data-editable="{{ $draft ? 1 : 0 }}">

        {{-- Header --}}
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
            <div>
                <h5 class="mb-1">{{ $schedule->title }}</h5>
                <span class="badge bg-{{ $badge }}">{{ ucfirst($schedule->status) }}</span>
                <span class="text-muted small ms-2"><i class="bi bi-calendar-event"></i> Statutory remittance due {{ $schedule->remittanceDue()->format('d M Y') }}</span>
            </div>
            <div class="d-flex gap-1 flex-wrap">
                <a href="{{ route('payroll.schedules.excel', $schedule) }}" class="btn btn-sm btn-outline-success"><i class="bi bi-file-earmark-excel"></i> Excel</a>
                <a href="{{ route('payroll.schedules.pdf', $schedule) }}" target="_blank" class="btn btn-sm btn-outline-danger"><i class="bi bi-file-earmark-pdf"></i> PDF</a>
                @if($draft)
                    <form method="POST" action="{{ route('payroll.schedules.regenerate', $schedule) }}" class="d-inline" onsubmit="return confirm('Rebuild from the staff register? All adjustments on this schedule will be lost.')">
                        @csrf<button class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-repeat"></i> Regenerate</button>
                    </form>
                    <form method="POST" action="{{ route('payroll.schedules.approve', $schedule) }}" class="d-inline" onsubmit="return confirm('Approve and lock this schedule? Unsaved changes are not included.')">
                        @csrf<button class="btn btn-sm btn-primary"><i class="bi bi-check2-all"></i> Approve</button>
                    </form>
                    <form method="POST" action="{{ route('payroll.schedules.destroy', $schedule) }}" class="d-inline" onsubmit="return confirm('Delete this draft?')">
                        @csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i> Delete</button>
                    </form>
                @endif
            </div>
        </div>

        @if($missing)<div class="alert alert-warning py-2 small"><i class="bi bi-exclamation-triangle"></i> {{ $missing }} staff have a missing KRA PIN, NSSF/SHIF number or payment detail. Fix them on the Staff Payees page before filing returns.</div>@endif
        <div id="breachAlert" class="alert alert-danger py-2 small {{ $schedule->lines->where('breaches_one_third', true)->count() ? '' : 'd-none' }}">
            <i class="bi bi-exclamation-octagon"></i> Highlighted rows leave the employee with less than one-third of pay (Employment Act s.19). Reduce deductions on those rows.
        </div>

        {{-- Summary --}}
        <div class="row g-2 mb-3">
            @foreach(['cGross'=>['Gross',$schedule->total_gross],'cPaye'=>['PAYE',$schedule->total_paye],'cNssf'=>['NSSF',$schedule->total_nssf],'cShif'=>['SHIF',$schedule->total_shif],'cAhl'=>['Housing Levy',$schedule->total_housing_levy],'cNet'=>['Net Pay',$schedule->total_net]] as $id => [$label, $val])
                <div class="col-6 col-md-2">
                    <div class="card ps-stat"><div class="card-body py-2 px-3">
                            <div class="lbl">{{ $label }}</div>
                            <div class="val" id="{{ $id }}">{{ number_format($val, 2) }}</div>
                        </div></div>
                </div>
            @endforeach
        </div>

        {{-- Lines --}}
        <form method="POST" action="{{ route('payroll.schedules.lines', $schedule) }}" id="linesForm">
            @csrf @method('PUT')
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center py-2">
                    <span class="fw-semibold">Staff lines <span class="text-muted fw-normal">({{ $schedule->lines->count() }})</span></span>
                    @if($draft)<span class="small text-muted"><i class="bi bi-lightning-charge"></i> Figures update as you type. Click Save to keep your changes.</span>@endif
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover ps-table mb-0 w-100">
                        <thead>
                        <tr>
                            <th class="ps-3">Name</th>
                            <th class="text-center">Days</th>
                            <th>Adjustments</th>
                            <th class="num">Gross</th>
                            <th class="num">NSSF</th>
                            <th class="num">SHIF</th>
                            <th class="num">AHL</th>
                            <th class="num">PAYE</th>
                            <th class="num">Other Ded.</th>
                            <th class="num">Non-taxable</th>
                            <th class="num">Net</th>
                            <th class="pe-3">Pay via</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($schedule->lines as $l)
                            @php $items = $l->items->map(fn ($i) => ['kind' => $i->kind, 'name' => $i->name, 'amount' => (float) $i->amount])->values(); @endphp
                            <tr class="line-row {{ $l->breaches_one_third ? 'table-danger' : '' }}"
                                data-id="{{ $l->id }}" data-name="{{ $l->full_name }}"
                                data-basic="{{ $l->basic_salary }}" data-dim="{{ $l->days_in_month }}" data-days="{{ $l->days_worked }}"
                                data-taxable="{{ $l->taxable_allowances }}" data-nontax="{{ $l->non_taxable_allowances }}"
                                data-pension="{{ $l->pension }}" data-ins="{{ $l->insurance_premium }}" data-mort="{{ $l->mortgage_interest }}"
                                data-other="{{ $l->other_deductions }}"
                                data-items="{{ json_encode($items) }}">
                                <td class="ps-3">
                                    <div class="fw-semibold">{{ $l->full_name }}</div>
                                    <div class="small text-muted">{{ $l->staff_no }} {{ $l->kra_pin }}</div>
                                </td>
                                <td class="text-center">
                                    @if($draft)
                                        <input type="number" min="0" max="{{ $l->days_in_month }}" name="lines[{{ $l->id }}][days_worked]" value="{{ $l->days_worked }}" class="form-control form-control-sm ps-days mx-auto">
                                    @else
                                        {{ $l->days_worked }}/{{ $l->days_in_month }}
                                    @endif
                                </td>
                                <td class="text-nowrap">
                                    <button type="button" class="btn btn-sm btn-outline-secondary js-adjust"><i class="bi bi-sliders"></i> {{ $draft ? 'Adjust' : 'View' }}</button>
                                    <span class="js-adj ms-1"></span>
                                    <div class="items-store d-none"></div>
                                </td>
                                <td class="num js-gross">{{ number_format($l->gross_pay, 2) }}</td>
                                <td class="num js-nssf">{{ number_format($l->nssf, 2) }}</td>
                                <td class="num js-shif">{{ number_format($l->shif, 2) }}</td>
                                <td class="num js-ahl">{{ number_format($l->housing_levy, 2) }}</td>
                                <td class="num js-paye">{{ number_format($l->paye, 2) }}</td>
                                <td class="num js-ded">{{ number_format($ded($l), 2) }}</td>
                                <td class="num js-nontax">{{ number_format($nonTax($l), 2) }}</td>
                                <td class="num fw-bold js-net">{{ number_format($l->net_pay, 2) }}</td>
                                <td class="pe-3"><span class="badge bg-light text-dark border">{{ strtoupper($l->payment_method) }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="12" class="text-center text-muted py-4">No active staff were employed in this period. Activate staff on the Staff Payees page, then Regenerate.</td></tr>
                        @endforelse
                        </tbody>
                        @if($schedule->lines->count())
                            <tfoot>
                            <tr>
                                <td class="ps-3" colspan="3">Total</td>
                                <td class="num" id="tGross">{{ number_format($schedule->lines->sum('gross_pay'), 2) }}</td>
                                <td class="num" id="tNssf">{{ number_format($schedule->lines->sum('nssf'), 2) }}</td>
                                <td class="num" id="tShif">{{ number_format($schedule->lines->sum('shif'), 2) }}</td>
                                <td class="num" id="tAhl">{{ number_format($schedule->lines->sum('housing_levy'), 2) }}</td>
                                <td class="num" id="tPaye">{{ number_format($schedule->lines->sum('paye'), 2) }}</td>
                                <td class="num" id="tDed">{{ number_format($schedule->lines->sum($ded), 2) }}</td>
                                <td class="num" id="tNontax">{{ number_format($schedule->lines->sum($nonTax), 2) }}</td>
                                <td class="num" id="tNet">{{ number_format($schedule->lines->sum('net_pay'), 2) }}</td>
                                <td class="pe-3"></td>
                            </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
                @if($draft && $schedule->lines->count())
                    <div class="card-footer bg-white d-flex align-items-center py-2">
                        <span id="dirtyNote" class="small text-warning d-none"><i class="bi bi-exclamation-circle"></i> Unsaved changes</span>
                        <button type="submit" class="btn btn-sm btn-primary ms-auto"><i class="bi bi-check2-circle"></i> Save &amp; Recalculate</button>
                    </div>
                @endif
            </div>
        </form>

        <div class="card border-0 shadow-sm mt-3"><div class="card-body py-2 small text-muted">
                Employer cost this month: <strong class="text-dark" id="cCost">{{ number_format($schedule->total_employer_cost, 2) }}</strong>
                (gross + non-taxable pay + employer NSSF + employer Housing Levy + NITA).
                @if($schedule->status === 'paid') &nbsp;|&nbsp; Paid on {{ $schedule->paid_on->format('d M Y') }}@if($schedule->payment_reference), Ref: {{ $schedule->payment_reference }}@endif @endif
            </div></div>

        @if($schedule->status === 'approved')
            <form method="POST" action="{{ route('payroll.schedules.paid', $schedule) }}" class="row g-2 mt-2">
                @csrf
                <div class="col-md-2"><input type="date" name="paid_on" value="{{ now()->format('Y-m-d') }}" class="form-control form-control-sm" required></div>
                <div class="col-md-4"><input name="payment_reference" class="form-control form-control-sm" placeholder="Payment reference (bank batch / M-Pesa ref)"></div>
                <div class="col-md-2"><button class="btn btn-sm btn-success w-100"><i class="bi bi-cash-coin"></i> Mark as Paid</button></div>
            </form>
        @endif
    </div>

    {{-- Adjustments modal --}}
    <datalist id="adjSuggest">
        @foreach(['Overtime','Bonus','Commission','Arrears','Transport reimbursement','Airtime reimbursement','Medical reimbursement','Salary advance','Fine','Loan repayment','Sacco contribution','Union dues','HELB','Absence deduction'] as $s)<option value="{{ $s }}">@endforeach
    </datalist>

    <div class="modal fade" id="adjModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header py-2">
                    <div>
                        <h6 class="modal-title mb-0" id="adjTitle">Adjustments</h6>
                        <div class="small text-muted">{{ $schedule->period->format('F Y') }}</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="adj-legend small text-muted mb-3">
                        <div><span class="badge bg-success">Taxable earning</span> Added to gross. PAYE, NSSF, SHIF and Housing Levy apply (bonus, overtime, commission).</div>
                        <div><span class="badge bg-info text-dark">Non-taxable reimbursement</span> Added to net pay only. No statutory deductions.</div>
                        <div><span class="badge bg-danger">Deduction</span> Taken from net pay after tax (advance, fine, loan, sacco).</div>
                    </div>
                    <div class="row g-2 mb-1 adj-head d-none d-md-flex">
                        <div class="col-md-4">Type</div><div class="col-md-4">Description</div><div class="col-md-3 text-end">Amount (KES)</div><div class="col-md-1"></div>
                    </div>
                    <div id="adjRows"></div>
                    <div class="text-muted small text-center py-3 d-none" id="adjEmpty">No adjustments for this staff member.</div>
                    @if($draft)<button type="button" class="btn btn-sm btn-outline-primary mt-2" id="adjAdd"><i class="bi bi-plus-lg"></i> Add item</button>@endif
                </div>
                <div class="modal-footer py-2 justify-content-between">
                    <div class="small" id="adjPreview"></div>
                    <div class="d-flex gap-1">
                        <button type="button" class="btn btn-sm btn-light border" data-bs-dismiss="modal">{{ $draft ? 'Cancel' : 'Close' }}</button>
                        @if($draft)<button type="button" class="btn btn-sm btn-primary" id="adjApply"><i class="bi bi-check2"></i> Apply</button>@endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(function () {
            const $root    = $('#psRoot');
            const CFG      = $root.data('config');
            const KINDS    = $root.data('kinds');
            const EDITABLE = Number($root.data('editable')) === 1;

            const num = v => parseFloat(v) || 0;
            const r2  = v => Math.round((v + Number.EPSILON) * 100) / 100;
            const fmt = v => Number(v).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

            /* ---- Kenyan payroll maths (mirror of KenyaPayrollCalculator.php; server stays authoritative on save) ---- */
            function bandTax(t) {
                let tax = 0, prev = 0;
                for (const b of CFG.paye_bands) {
                    const lim = b.upto === null ? Infinity : b.upto;
                    if (t <= prev) break;
                    tax += (Math.min(t, lim) - prev) * b.rate;
                    prev = lim;
                }
                return r2(tax);
            }

            function calc(i) {
                const gross  = r2(i.basic + i.taxable);
                const nonTax = r2(i.nonTaxable);
                const lel    = CFG.nssf.lower_earnings_limit;
                const pens   = Math.min(gross, CFG.nssf.upper_earnings_limit);
                const nssf   = r2(Math.min(pens, lel) * CFG.nssf.rate + Math.max(0, pens - lel) * CFG.nssf.rate);
                const shif   = gross > 0 ? Math.max(CFG.shif.minimum, r2(gross * CFG.shif.rate)) : 0;
                const ahl    = r2(gross * CFG.housing_levy.employee_rate);
                const pD     = Math.min(i.pension, CFG.pension_deduction_cap);
                const mD     = Math.min(i.mortgage, CFG.mortgage_interest_cap);
                const taxable = Math.max(0, r2(gross - nssf - shif - ahl - pD - mD));
                const insR   = Math.min(CFG.insurance_relief_cap, r2(i.premium * CFG.insurance_relief_rate));
                const paye   = gross > 0 ? Math.max(0, r2(bandTax(taxable) - CFG.personal_relief - insR)) : 0;
                const totalPay = gross + nonTax;
                const net    = r2(totalPay - (nssf + shif + ahl + paye + i.pension + i.other));
                return {
                    gross, nssf, shif, ahl, paye, net, nonTax,
                    ded: r2(i.pension + i.other),
                    erNssf: nssf,
                    erAhl: r2(gross * CFG.housing_levy.employer_rate),
                    nita: gross > 0 ? CFG.nita_per_employee : 0,
                    breach: totalPay > 0 && net < r2(totalPay * CFG.min_net_fraction)
                };
            }

            /* ---- Row helpers ---- */
            const sums = items => ({
                allowance:     r2(items.filter(i => i.kind === 'allowance').reduce((a, i) => a + num(i.amount), 0)),
                reimbursement: r2(items.filter(i => i.kind === 'reimbursement').reduce((a, i) => a + num(i.amount), 0)),
                deduction:     r2(items.filter(i => i.kind === 'deduction').reduce((a, i) => a + num(i.amount), 0))
            });

            function daysOf($row) {
                const dim = num($row.data('dim'));
                if (!EDITABLE) return num($row.data('days'));
                return Math.max(0, Math.min(dim, parseInt($row.find('.ps-days').val(), 10) || 0));
            }

            function inputsFor($row, items) {
                const dim = num($row.data('dim'));
                const f   = dim > 0 ? Math.min(1, daysOf($row) / dim) : 1;
                const t   = sums(items);
                return {
                    basic:      r2(num($row.data('basic')) * f),
                    taxable:    num($row.data('taxable')) + t.allowance,
                    nonTaxable: num($row.data('nontax')) + t.reimbursement,
                    pension:    num($row.data('pension')),
                    premium:    num($row.data('ins')),
                    mortgage:   num($row.data('mort')),
                    other:      num($row.data('other')) + t.deduction
                };
            }

            function renderBadges($row) {
                const items = $row.data('items') || [];
                const t = sums(items);
                const plus = t.allowance + t.reimbursement;
                let html = '';
                if (plus > 0)        html += `<span class="badge bg-success-subtle text-success-emphasis border border-success-subtle">+${fmt(plus)}</span> `;
                if (t.deduction > 0) html += `<span class="badge bg-danger-subtle text-danger-emphasis border border-danger-subtle">&minus;${fmt(t.deduction)}</span>`;
                $row.find('.js-adj').html(html);
            }

            function syncHidden($row) {
                const id = $row.data('id');
                const $s = $row.find('.items-store').empty();
                ($row.data('items') || []).forEach((it, n) => {
                    ['kind', 'name', 'amount'].forEach(f =>
                        $('<input>', { type: 'hidden', name: `lines[${id}][items][${n}][${f}]`, value: it[f] }).appendTo($s));
                });
            }

            function refreshRow($row) {
                const res = calc(inputsFor($row, $row.data('items') || []));
                $row.data('res', res);
                $row.find('.js-gross').text(fmt(res.gross));
                $row.find('.js-nssf').text(fmt(res.nssf));
                $row.find('.js-shif').text(fmt(res.shif));
                $row.find('.js-ahl').text(fmt(res.ahl));
                $row.find('.js-paye').text(fmt(res.paye));
                $row.find('.js-ded').text(fmt(res.ded));
                $row.find('.js-nontax').text(fmt(res.nonTax));
                $row.find('.js-net').text(fmt(res.net));
                $row.toggleClass('table-danger', res.breach);
                renderBadges($row);
            }

            function refreshTotals() {
                const t = { gross: 0, nssf: 0, shif: 0, ahl: 0, paye: 0, ded: 0, nonTax: 0, net: 0, cost: 0 };
                let breaches = 0;
                $('.line-row').each(function () {
                    const r = $(this).data('res'); if (!r) return;
                    t.gross += r.gross; t.nssf += r.nssf; t.shif += r.shif; t.ahl += r.ahl; t.paye += r.paye;
                    t.ded += r.ded; t.nonTax += r.nonTax; t.net += r.net;
                    t.cost += r.gross + r.nonTax + r.erNssf + r.erAhl + r.nita;
                    if (r.breach) breaches++;
                });
                const set = (id, v) => $(id).text(fmt(v));
                set('#cGross', t.gross); set('#cPaye', t.paye); set('#cNssf', t.nssf);
                set('#cShif', t.shif);   set('#cAhl', t.ahl);   set('#cNet', t.net); set('#cCost', t.cost);
                set('#tGross', t.gross); set('#tNssf', t.nssf); set('#tShif', t.shif); set('#tAhl', t.ahl);
                set('#tPaye', t.paye);   set('#tDed', t.ded);   set('#tNontax', t.nonTax); set('#tNet', t.net);
                $('#breachAlert').toggleClass('d-none', breaches === 0);
            }

            const markDirty = () => $('#dirtyNote').removeClass('d-none');

            /* ---- Initial state ---- */
            $('.line-row').each(function () {
                const $row = $(this);
                if (EDITABLE) { refreshRow($row); syncHidden($row); }  // re-post existing items so they are not wiped on save
                else renderBadges($row);
            });
            if (EDITABLE) refreshTotals();

            if (EDITABLE) {
                $('.ps-days').on('input', function () {
                    refreshRow($(this).closest('tr')); refreshTotals(); markDirty();
                }).on('change', function () {
                    const $row = $(this).closest('tr');
                    $(this).val(daysOf($row)); refreshRow($row); refreshTotals();
                });
            }

            /* ---- Adjustments modal ---- */
            const modalEl = document.getElementById('adjModal');
            const modal   = bootstrap.Modal.getOrCreateInstance(modalEl);
            let $active = null;

            function toggleEmpty() {
                $('#adjEmpty').toggleClass('d-none', $('#adjRows .adj-row').length > 0);
            }

            function addModalRow(it) {
                const opts = Object.entries(KINDS).map(([k, l]) => `<option value="${k}" ${k === it.kind ? 'selected' : ''}>${l}</option>`).join('');
                const $r = $(`<div class="row g-2 mb-2 align-items-center adj-row">
            <div class="col-md-4"><select class="form-select form-select-sm adj-kind">${opts}</select></div>
            <div class="col-md-4"><input class="form-control form-control-sm adj-name" list="adjSuggest" placeholder="e.g. Salary advance" maxlength="255"></div>
            <div class="col-md-3"><input type="number" step="0.01" min="0" class="form-control form-control-sm adj-amount text-end" placeholder="0.00"></div>
            <div class="col-md-1 text-end"><button type="button" class="btn btn-sm btn-outline-danger adj-remove" title="Remove"><i class="bi bi-trash"></i></button></div>
        </div>`);
                $r.find('.adj-name').val(it.name || '');
                $r.find('.adj-amount').val(it.amount === '' || it.amount == null ? '' : it.amount);
                if (!EDITABLE) { $r.find('input,select').prop('disabled', true); $r.find('.adj-remove').remove(); }
                $('#adjRows').append($r);
                toggleEmpty();
            }

            function readModal(markInvalid) {
                const items = []; let ok = true;
                $('#adjRows .adj-row').each(function () {
                    const $r = $(this);
                    const name = $r.find('.adj-name').val().trim();
                    const raw  = $r.find('.adj-amount').val();
                    const amt  = parseFloat(raw);
                    if (!name && !raw) return;                         // fully blank row: ignore
                    const badName = !name, badAmt = !(amt > 0);
                    if (markInvalid) {
                        $r.find('.adj-name').toggleClass('is-invalid', badName);
                        $r.find('.adj-amount').toggleClass('is-invalid', badAmt);
                    }
                    if (badName || badAmt) { ok = false; return; }
                    items.push({ kind: $r.find('.adj-kind').val(), name, amount: r2(amt) });
                });
                return { items, ok };
            }

            function updatePreview() {
                if (!$active) return;
                const res = calc(inputsFor($active, readModal(false).items));
                $('#adjPreview').html(
                    `Gross <strong>${fmt(res.gross)}</strong> &middot; PAYE <strong>${fmt(res.paye)}</strong> &middot; ` +
                    `Net <strong class="${res.breach ? 'text-danger' : 'text-success'}">${fmt(res.net)}</strong>` +
                    (res.breach ? ' <span class="badge bg-danger">&lt; 1/3 of pay</span>' : '')
                );
            }

            $('.js-adjust').on('click', function () {
                $active = $(this).closest('tr');
                const items = $active.data('items') || [];
                $('#adjTitle').text($active.data('name'));
                $('#adjRows').empty();
                items.forEach(addModalRow);
                if (EDITABLE && !items.length) addModalRow({ kind: 'allowance', name: '', amount: '' });
                toggleEmpty();
                updatePreview();
                modal.show();
            });

            $('#adjAdd').on('click', () => { addModalRow({ kind: 'allowance', name: '', amount: '' }); updatePreview(); });

            $('#adjRows').on('click', '.adj-remove', function () {
                $(this).closest('.adj-row').remove(); toggleEmpty(); updatePreview();
            }).on('input change', 'input,select', function () {
                $(this).removeClass('is-invalid'); updatePreview();
            });

            $('#adjApply').on('click', function () {
                const { items, ok } = readModal(true);
                if (!ok) return;
                $active.data('items', items);
                syncHidden($active);
                refreshRow($active);
                refreshTotals();
                markDirty();
                modal.hide();
            });
        });
    </script>
@endpush
