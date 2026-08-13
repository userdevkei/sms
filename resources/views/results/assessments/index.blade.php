@extends('layouts.app')
@section('title', 'Assessments')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <div>
            <h1 class="h4 mb-1">Assessments</h1>
            <p class="text-muted mb-0">Assessment rounds - grouped by name and term.</p>
        </div>
        <a href="{{ route('results.assessments.create') }}" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg me-1"></i> New Assessment</a>
    </div>

    <x-results-tabs active="assessments" />

    @if($subjectResultGroups->isNotEmpty())
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body">
                <h2 class="h6 mb-2">Finalize Subject Results</h2>
                <p class="text-muted small mb-2">Average marks per subject/class/term into a single result before it can feed into report card compilation.</p>
                <div class="list-group list-group-flush">
                    @foreach($subjectResultGroups as $group)
                        <a href="{{ route('results.term-subject.preview', [$group['stream_id'], $group['learning_area_id'], $group['academic_term_id']]) }}"
                           class="list-group-item list-group-item-action d-flex justify-content-between align-items-center px-0">
                            <span>{{ $group['subject'] }} &middot; {{ $group['class'] }} &middot; {{ $group['term'] }}</span>
                            <span class="badge bg-secondary-subtle text-secondary">{{ $group['assessment_count'] }} assessment(s)</span>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            @if($rounds->isEmpty())
                <div class="text-center py-5 text-muted">No assessments found - or you have no subject assignments yet.</div>
            @else
                <div class="table-responsive">
                    <table id="roundsTable" class="table table-hover table-sm table-striped fs-sm w-100">
                        <thead>
                        <tr>
                            <th>#</th>
                            <th>Assessment</th>
                            <th>Term</th>
                            <th class="text-center">Total</th>
                            <th class="text-center">Draft</th>
                            <th class="text-center">Open</th>
                            <th class="text-center">Locked</th>
                            <th class="text-end">Action</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($rounds as $round)
                            @php
                                $roundUrl = route('results.assessments.round', [$round['academic_term_id'], urlencode($round['name'])]);
                            @endphp
                            <tr class="cursor-pointer" onclick="window.location='{{ $roundUrl }}'">
                                <td>{{ $loop->iteration }}</td>
                                <td class="fw-semibold text-dark">{{ $round['name'] }}</td>
                                <td class="text-muted">{{ $round['term_label'] }}</td>
                                <td class="text-center">
                                    <span class="badge bg-secondary-subtle text-secondary">{{ $round['total'] }}</span>
                                </td>
                                <td class="text-center">
                                    @if($round['draft'] > 0)
                                        <span class="badge bg-secondary-subtle text-secondary">{{ $round['draft'] }}</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($round['open'] > 0)
                                        <span class="badge bg-success-subtle text-success">{{ $round['open'] }}</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($round['locked'] > 0)
                                        <span class="badge bg-warning-subtle text-warning">{{ $round['locked'] }}</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-end" onclick="event.stopPropagation()">
                                    <a href="{{ $roundUrl }}" class="btn btn-sm btn-outline-primary">View</a>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@endsection

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
    <style>.cursor-pointer { cursor: pointer; }</style>
@endpush
@push('scripts')
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $('#roundsTable').DataTable({
            order: [[0, 'asc']],
            pageLength: 50,
            columnDefs: [{ targets: -1, orderable: false }]
        });
    </script>
@endpush
