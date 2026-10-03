@extends('layouts.app')
@section('content')
@php
    $editing = $payee->exists;
    $items = old('items', $payee->items?->map(fn($i)=>['kind'=>$i->kind,'name'=>$i->name,'amount'=>$i->amount])->values()->all() ?? []);
    $kinds = \App\Models\StaffPayItem::KINDS;
@endphp
<div class="container-fluid">
    <h4 class="mb-3 breadcrum">{{ $editing ? 'Edit' : 'Add' }} Staff Payee</h4>
    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

    <form method="POST" action="{{ $editing ? route('payroll.payees.update', $payee) : route('payroll.payees.store') }}">
        @csrf @if($editing) @method('PUT') @endif

        @php $linked = old('user_id', $payee->user_id); @endphp
        <div class="card mb-3"><div class="card-header">Staff source</div><div class="card-body row g-3">
            <div class="col-12">
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="source" id="srcUser" value="user" @checked($linked)>
                    <label class="form-check-label" for="srcUser">Existing system user</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="source" id="srcNew" value="new" @checked(! $linked)>
                    <label class="form-check-label" for="srcNew">New staff (not a system user)</label>
                </div>
            </div>
            <div class="col-md-6" id="userPick">
                <label class="form-label">Select user</label>
                <select name="user_id" id="user_id" class="form-select" data-url="{{ route('payroll.payees.users') }}" style="width:100%">
                    @if($linked)<option value="{{ $linked }}" selected>{{ $payee->full_name ?: $linked }}</option>@endif
                </select>
                <div class="form-text">Students and users already on the payee list are not shown. Name, staff no and phone are pre-filled; you can edit them.</div>
            </div>
        </div></div>

        <div class="card mb-3"><div class="card-header">Identity & statutory numbers</div><div class="card-body row g-3">
            @foreach([['full_name','Full name',1],['staff_no','Staff No',0],['id_number','National ID',0],['kra_pin','KRA PIN',0],['nssf_no','NSSF No',0],['shif_no','SHA / SHIF No',0],['designation','Designation',0],['department','Department',0]] as [$f,$label,$req])
                <div class="col-md-3"><label class="form-label">{{ $label }}</label>
                <input name="{{ $f }}" value="{{ old($f, $payee->$f) }}" class="form-control" @if($req) required @endif></div>
            @endforeach
            <div class="col-md-3"><label class="form-label">Employment type</label>
                <select name="employment_type" class="form-select">
                    @foreach(['permanent','contract','casual'] as $t)<option value="{{ $t }}" @selected(old('employment_type',$payee->employment_type)==$t)>{{ ucfirst($t) }}</option>@endforeach
                </select></div>
            <div class="col-md-3"><label class="form-label">Start date</label><input type="date" name="start_date" value="{{ old('start_date', optional($payee->start_date)->format('Y-m-d')) }}" class="form-control"></div>
            <div class="col-md-3"><label class="form-label">End date</label><input type="date" name="end_date" value="{{ old('end_date', optional($payee->end_date)->format('Y-m-d')) }}" class="form-control"></div>
            <div class="col-md-3"><label class="form-label">Basic salary (KES / month)</label><input type="number" step="0.01" min="0" name="basic_salary" value="{{ old('basic_salary', $payee->basic_salary) }}" class="form-control" required></div>
        </div></div>

        <div class="card mb-3"><div class="card-header">Payment details</div><div class="card-body row g-3">
            <div class="col-md-3"><label class="form-label">Pay via</label>
                <select name="payment_method" class="form-select">
                    @foreach(['bank'=>'Bank','mpesa'=>'M-Pesa','cash'=>'Cash'] as $v=>$l)<option value="{{ $v }}" @selected(old('payment_method',$payee->payment_method)==$v)>{{ $l }}</option>@endforeach
                </select></div>
            @foreach([['bank_name','Bank'],['bank_branch','Branch'],['bank_account','Account No'],['mpesa_phone','M-Pesa phone']] as [$f,$label])
                <div class="col-md-3"><label class="form-label">{{ $label }}</label><input name="{{ $f }}" value="{{ old($f, $payee->$f) }}" class="form-control"></div>
            @endforeach
            <div class="col-12 form-check ms-2">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1" @checked(old('is_active', $payee->is_active))>
                <label class="form-check-label" for="is_active">Active (included in new schedules)</label>
            </div>
        </div></div>

        <div class="card mb-3"><div class="card-header d-flex justify-content-between">
            <span>Recurring allowances & deductions</span>
            <button type="button" class="btn btn-sm btn-outline-primary" id="addItem">+ Add line</button>
        </div><div class="card-body">
            <div class="small text-muted mb-2">Statutory NSSF, SHIF, Housing Levy and PAYE are calculated automatically — do not add them here.</div>
            <div id="itemRows" data-config='@json(['kinds'=>$kinds,'items'=>$items])'></div>
        </div></div>

        <button class="btn btn-sm btn-primary">Save</button>
        <a href="{{ route('payroll.payees.index') }}" class="btn btn-sm btn-light">Cancel</a>
    </form>
</div>
@endsection
@push('scripts')
<script>
$(function(){
    const cfg = JSON.parse($('#itemRows').attr('data-config'));
    let idx = 0;
    function row(it){
        it = it || {kind:'allowance', name:'', amount:''};
        const opts = Object.entries(cfg.kinds).map(([k,l]) => `<option value="${k}" ${k===it.kind?'selected':''}>${l}</option>`).join('');
        const html = $(`<div class="row g-2 mb-2 item-row">
            <div class="col-md-4"><select class="form-select" name="items[${idx}][kind]">${opts}</select></div>
            <div class="col-md-4"><input class="form-control" placeholder="Description" name="items[${idx}][name]"></div>
            <div class="col-md-3"><input type="number" step="0.01" min="0" class="form-control" placeholder="Amount" name="items[${idx}][amount]"></div>
            <div class="col-md-1"><button type="button" class="btn btn-sm btn-outline-danger w-100 rm">&times;</button></div></div>`);
        html.find('input[name$="[name]"]').val(it.name);
        html.find('input[name$="[amount]"]').val(it.amount);
        $('#itemRows').append(html); idx++;
    }
    cfg.items.forEach(row);
    $('#addItem').on('click', () => row());
    $('#itemRows').on('click', '.rm', function(){ $(this).closest('.item-row').remove(); });
});

$(function(){
    const $sel = $('#user_id');
    $sel.select2({
        placeholder: 'Search by name, ID or email…', allowClear: true, width: '100%',
        ajax: { url: $sel.data('url'), dataType: 'json', delay: 250, data: p => ({q: p.term || ''}), processResults: d => d },
        minimumInputLength: 0
    });
    function fillIfEmpty(sel, v){ const $f = $(sel); if (v && !$f.val()) $f.val(v); }
    $sel.on('select2:select', function(e){
        const u = e.params.data;
        $('[name=full_name]').val(u.name || $('[name=full_name]').val());
        fillIfEmpty('[name=staff_no]', u.staff_no);
        fillIfEmpty('[name=mpesa_phone]', u.phone);
    });
    function toggleSource(){
        const useUser = $('#srcUser').is(':checked');
        $('#userPick').toggle(useUser);
        if (!useUser) $sel.val(null).trigger('change');
    }
    $('input[name=source]').on('change', toggleSource);
    toggleSource();
});
</script>
@endpush
