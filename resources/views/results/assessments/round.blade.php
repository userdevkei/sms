@extends('layouts.app')
@section('title', $name)

@section('content')
    <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
            <h1 class="h4 mb-1">{{ $name }}</h1>
            <p class="text-muted mb-0">{{ $academicTerm->academic_year }} Term {{ $academicTerm->term_number }} &middot; subjects and classes assessed under this round.</p>
        </div>
        <a href="{{ route('results.assessments.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Back</a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table id="roundTable" class="table table-hover align-middle w-100 table-striped">
                    <thead>
                    <tr>
                        <th>#</th><th>Subject</th><th>Class</th><th>Type</th><th>Max Score</th><th>Status</th><th>Finalized</th><th class="text-end">Actions</th>
                    </tr>
                    </thead>
                    <tbody></tbody>
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
        const roundDataUrl = @json(route('results.assessments.round-data', [$academicTerm->id, urlencode($name)]));
    </script>
    <script src="{{ asset('js/assessments-round.js') }}"></script>
@endpush
