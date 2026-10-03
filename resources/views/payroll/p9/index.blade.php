@extends('layouts.app')
@section('content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <div>
                <h5 class="mb-0">P9 Tax Deduction Cards</h5>
                <div class="small text-muted">Built from approved and paid schedules only.</div>
            </div>
            <div class="d-flex gap-2">
                <form method="GET" class="d-flex gap-2">
                    <select name="year" class="form-select form-select-sm" onchange="this.form.submit()">
                        @foreach($years as $y)<option value="{{ $y }}" @selected($y == $year)>{{ $y }}</option>@endforeach
                    </select>
                </form>
                @if($rows->count())
                    <a href="{{ route('payroll.p9.all', ['year' => $year]) }}" target="_blank" class="btn btn-sm btn-primary"><i class="bi bi-printer"></i> Print all P9 ({{ $rows->count() }})</a>
                @endif
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-2">
                <input type="search" id="p9Filter" class="form-control form-control-sm" style="max-width:280px" placeholder="Search staff…">
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-striped table-hover align-middle mb-0" id="p9Table">
                    <thead class="table-light">
                    <tr><th class="ps-3" style="width:50px">#</th><th>Staff No</th><th>Name</th><th>KRA PIN</th><th class="text-center">Months</th><th class="text-end">Gross pay</th><th class="text-end">PAYE</th><th class="text-end pe-3" style="width:110px"></th></tr>
                    </thead>
                    <tbody>
                    @forelse($rows as $r)
                        <tr>
                            <td class="ps-3">{{ $loop->iteration }}</td>
                            <td>{{ $r->staff_no }}</td>
                            <td>{{ $r->full_name }}</td>
                            <td>{!! $r->kra_pin ?: '<span class="badge bg-warning text-dark">Missing</span>' !!}</td>
                            <td class="text-center">{{ $r->months }}/12</td>
                            <td class="text-end">{{ number_format($r->gross, 2) }}</td>
                            <td class="text-end">{{ number_format($r->paye, 2) }}</td>
                            <td class="text-end pe-3"><a href="{{ route('payroll.p9.show', ['payee' => $r->id, 'year' => $year]) }}" target="_blank" class="btn btn-sm btn-outline-primary"><i class="bi bi-printer"></i> P9</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted py-4">No approved schedules in {{ $year }}.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(function () {
            $('#p9Filter').on('input', function () {
                const q = this.value.toLowerCase();
                $('#p9Table tbody tr').each(function () { $(this).toggle($(this).text().toLowerCase().includes(q)); });
            });
        });
    </script>
@endpush
