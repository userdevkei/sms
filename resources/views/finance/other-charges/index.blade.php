@extends('layouts.app')
@section('title', 'Other Charges')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <div>
            <h1 class="h4 mb-1">Other Charges</h1>
            <p class="text-muted mb-0">One-off charges scoped to a student, stream, or grade level — applied automatically when invoices are generated.</p>
        </div>
        @can('other_charges.manage')
            <a href="{{ route('finance.other-charges.create') }}" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg me-1"></i> Add Charge</a>
        @endcan
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table id="chargesTable" class="table table-hover table-sm table-striped fs-sm w-100">
                    <thead>
                    <tr><th>#</th>
                        <th>Type</th><th>Scope</th><th>Description</th><th>Term</th>
                        <th class="text-end">Amount</th><th>Status</th>
                        @can('other_charges.manage')<th class="text-end">Actions</th>@endcan
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($charges as $charge)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $charge->type->name }}</td>
                            <td>
                                @if($charge->user_id)
                                    <span class="badge bg-info-subtle text-info">Student</span> {{ $charge->student->full_name ?? '—' }}
                                @elseif($charge->stream_id)
                                    <span class="badge bg-primary-subtle text-primary">Stream</span> {{ $charge->stream->name ?? '—' }}
                                @elseif($charge->grade_level_id)
                                    <span class="badge bg-secondary-subtle text-secondary">Grade</span> {{ $charge->gradeLevel->name ?? '—' }}
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-muted">{{ $charge->description ?: '—' }}</td>
                            <td>Term {{ $charge->term }}, {{ $charge->academic_year }}</td>
                            <td class="text-end">{{ number_format($charge->amount, 2) }}</td>
                            <td>
                                @php $map = ['active' => 'success', 'invoiced' => 'primary', 'cancelled' => 'secondary']; @endphp
                                <span class="badge bg-{{ $map[$charge->status] ?? 'secondary' }}-subtle text-{{ $map[$charge->status] ?? 'secondary' }} text-capitalize">{{ $charge->status }}</span>
                            </td>
                            @can('other_charges.manage')
                                <td class="text-end">
                                    @if($charge->status !== 'invoiced')
                                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="cancelCharge('{{ $charge->id }}')"><i class="bi bi-x-lg"></i> Cancel</button>
                                    @else
                                        <span class="text-muted small">Locked</span>
                                    @endif
                                </td>
                            @endcan
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('styles')<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">@endpush
@push('scripts')
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $('#chargesTable').DataTable({ order: [[3, 'desc']], pageLength: 25 });

        const cancelUrlTemplate = @json(route('finance.other-charges.destroy', ['other_charge' => '__ID__']));

        function cancelCharge(id) {
            if (! confirm('Cancel this charge? It will no longer apply to future invoices.')) return;

            fetch(cancelUrlTemplate.replace('__ID__', id), {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': @json(csrf_token()), 'Accept': 'application/json' }
            })
                .then(r => r.json())
                .then(data => { data.success ? location.reload() : alert(data.message); });
        }
    </script>
@endpush
