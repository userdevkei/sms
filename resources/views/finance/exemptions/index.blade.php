{{-- resources/views/finance/exemptions/index.blade.php --}}
@extends('layouts.app')
@section('title', 'Exemptions & Scholarships')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <div><h1 class="h4 mb-1">Exemptions & Scholarships</h1><p class="text-muted mb-0">Fee waivers, pending and resolved.</p></div>
        @can('exemptions.apply')<a href="{{ route('finance.exemptions.create') }}" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg me-1"></i> Request Exemption</a>@endcan
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <h6 class="text-uppercase text-muted small mb-3">Pending ({{ $pending->count() }})</h6>
            @forelse($pending as $exemption)
                <div class="border rounded p-3 mb-2">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                        <div>
                            <div class="fw-semibold">{{ $exemption->student->full_name }}
                                <span class="badge bg-info-subtle text-info ms-1">{{ $exemption->type === 'percentage' ? $exemption->value . '%' : 'KES ' . number_format($exemption->value, 0) }}</span>
                            </div>
                            <div class="text-muted small">{{ $exemption->scopeLabel() }} . Term {{ $exemption->term }}, {{ $exemption->academic_year }} . Requested by {{ $exemption->requestedBy->full_name }}</div>
                            <p class="mb-0 mt-1">{{ $exemption->reason }}</p>
                        </div>
                        @can('exemptions.approve')
                            <div class="d-flex gap-2">
                                <form method="POST" action="{{ route('finance.exemptions.approve', $exemption->id) }}" onsubmit="return confirm('Approve this exemption?')">@csrf<button type="submit" class="btn btn-sm btn-success"><i class="bi bi-check-lg"></i> Approve</button></form>
                                <form method="POST" action="{{ route('finance.exemptions.reject', $exemption->id) }}" onsubmit="return confirm('Reject this exemption?')">@csrf<button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-x-lg"></i> Reject</button></form>
                            </div>
                        @endcan
                    </div>
                </div>
            @empty
                <p class="text-muted small fst-italic mb-0">No pending exemption requests.</p>
            @endforelse
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <h6 class="text-uppercase text-muted small mb-3">Recently Resolved</h6>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0 w-100" id="resolvedExemptionsTable">
                    <thead><tr><th>#</th><th>Student NO.</th><th>Student</th><th>Applies To</th><th>Value</th><th>Decision</th><th>By</th></tr></thead>
                    <tbody>
                    @forelse($resolved as $exemption)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $exemption->student->userID }}</td>
                            <td>{{ $exemption->student->full_name }}</td>
                            <td>{{ $exemption->scopeLabel() }}</td>
                            <td data-order="{{ $exemption->value }}">{{ $exemption->type === 'percentage' ? $exemption->value . '%' : 'KES ' . number_format($exemption->value, 0) }}</td>
                            <td><span class="badge bg-{{ $exemption->status === 'approved' ? 'success' : 'danger' }}-subtle text-{{ $exemption->status === 'approved' ? 'success' : 'danger' }} text-capitalize">{{ $exemption->status }}</span></td>
                            <td>{{ $exemption->approvedBy?->full_name ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-3">No resolved exemptions yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
@endpush

@push('scripts')
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(function () {
            $('#resolvedExemptionsTable').DataTable({
                order: [[0, 'asc']], // Student name, alphabetical
                pageLength: 10,
                lengthMenu: [10, 25, 50, 100],
            });
        });
    </script>
@endpush
