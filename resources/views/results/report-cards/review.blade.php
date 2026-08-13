@extends('layouts.app')
@section('title', 'Review Report Cards')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <div>
            <h1 class="h4 mb-1">Review Report Cards</h1>
            <p class="text-muted mb-0">
                {{ $stream->full_name }} &middot; {{ $academicTerm->academic_year }} Term {{ $academicTerm->term_number }}
            </p>
        </div>
        <a href="{{ route('results.report-cards.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>

    @if($results->isEmpty())
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5 text-muted">
                No compiled results found for this stream and term. Compile them first from the report cards index.
            </div>
        </div>
    @else
        @php
            $draftCount = $results->where('status', 'draft')->count();
            $publishedCount = $results->where('status', 'published')->count();
        @endphp

        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <span class="badge bg-secondary-subtle text-secondary me-1">{{ $draftCount }} draft</span>
                    <span class="badge bg-success-subtle text-success">{{ $publishedCount }} published</span>
                </div>

                @if($draftCount > 0)
                    <form method="POST" action="{{ route('results.report-cards.publish') }}" id="publishAllForm">
                        @csrf
                        <input type="hidden" name="stream_id" value="{{ $stream->id }}">
                        <input type="hidden" name="academic_term_id" value="{{ $academicTerm->id }}">
                        <button type="submit" class="btn btn-sm btn-primary">
                            <i class="bi bi-send-check me-1"></i> Publish All ({{ $draftCount }})
                        </button>
                    </form>
                @else
                    <span class="text-muted small">All results in this stream have been published.</span>
                @endif
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table id="reviewTable" class="table table-hover table-sm table-striped fs-sm w-100">
                        <thead>
                        <tr>
                            <th>#</th>
                            <th>Pos (Stream)</th>
                            <th>Pos (Grade)</th>
                            <th>Student NO.</th>
                            <th>Student Name</th>
                            <th class="text-end">Total Score</th>
                            <th class="text-end">Average</th>
                            <th>Status</th>
                            <th class="text-end">PDF</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($results as $result)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td data-order="{{ $result->position_in_stream ?? 999999 }}">{{ $result->position_in_stream ?? '-' }} / {{ $result->stream_size ?? '-' }}</td>
                                <td data-order="{{ $result->position_in_grade ?? 999999 }}">{{ $result->position_in_grade ?? '-' }} / {{ $result->grade_size ?? '-' }}</td>
                                <td>{{ $result->enrollment->student->userID ?? '-' }}</td>
                                <td>{{ $result->enrollment->student->full_name ?? '-' }}</td>
                                <td class="text-end">{{ $result->total_score ?? '-' }}</td>
                                <td class="text-end">{{ $result->average_score ?? '-' }}</td>
                                <td>
                                    @if($result->status === 'published')
                                        <span class="badge bg-success-subtle text-success">Published</span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary">Draft</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('results.report-cards.pdf', $result->id) }}" class="btn btn-sm btn-outline-secondary" target="_blank">
                                        <i class="bi bi-file-earmark-pdf"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
@endsection

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
@endpush
@push('scripts')
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $('#reviewTable').DataTable({
            order: [[0, 'asc']],
            pageLength: 25,
            columnDefs: [{ targets: -1, orderable: false }]
        });

        document.getElementById('publishAllForm')?.addEventListener('submit', function (e) {
            if (!confirm('Publish all draft report cards for this stream? This cannot be undone from here.')) {
                e.preventDefault();
            }
        });
    </script>
@endpush
