@extends('layouts.app')
@section('title', $name . ' Report Cards')

@section('content')
    <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
            <h1 class="h4 mb-1">{{ $name }} Report Cards</h1>
            <p class="text-muted mb-0">{{ $stream->full_name }} &middot; {{ $academicTerm->academic_year }} Term {{ $academicTerm->term_number }}</p>
        </div>
        <a href="{{ route('results.report-cards.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Back</a>
    </div>

    <form method="GET" action="{{ route('results.report-cards.assessment-pdf-bulk', [$stream->id, $academicTerm->id, urlencode($name)]) }}" id="bulkForm" target="_blank">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <input type="checkbox" id="selectAll" class="form-check-input me-1"> <label for="selectAll" class="small">Select all</label>
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-sm btn-outline-primary" id="downloadSelectedBtn" disabled>
                        <i class="bi bi-download me-1"></i> Download Selected
                    </button>
                    <a href="{{ route('results.report-cards.assessment-pdf-bulk', [$stream->id, $academicTerm->id, urlencode($name)]) }}" class="btn btn-sm btn-primary" target="_blank">
                        <i class="bi bi-download me-1"></i> Download All
                    </a>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table id="reportCardsTable" class="table table-hover align-middle w-100 table-striped">
                        <thead>
                        <tr>
                            <th>#</th>
                            <th>Check</th><th>Position</th><th>Student NO.</th><th>Student Name</th>
                            <th class="text-end">Average</th><th class="text-end">Total</th><th class="text-end">PDF</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($students as $row)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>
                                    @if($row['average'] !== null)
                                        <input type="checkbox" name="enrollments[]" value="{{ $row['enrollment']->id }}" class="form-check-input student-checkbox">
                                    @endif
                                </td>
                                <td data-order="{{ $row['position'] ?? 999999 }}">{{ $row['position'] ? $row['position'] . ' / ' . $row['stream_size'] : '-' }}</td>
                                <td>{{ $row['enrollment']->student->userID ?? '-' }}</td>
                                <td>{{ $row['enrollment']->student->full_name ?? '-' }}</td>
                                <td class="text-end">{{ $row['average'] ?? '-' }}</td>
                                <td class="text-end">{{ $row['total'] ?? '-' }}</td>
                                <td class="text-end">
                                    @if($row['average'] !== null)
                                        <a href="{{ route('results.report-cards.assessment-pdf', [$stream->id, $academicTerm->id, urlencode($name), $row['enrollment']->id]) }}"
                                           class="btn btn-sm btn-outline-secondary" target="_blank">
                                            <i class="bi bi-file-earmark-pdf"></i>
                                        </a>
                                    @else
                                        <span class="text-muted small">No marks</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center text-muted py-3">No students found.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </form>
@endsection

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
@endpush
@push('scripts')
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
    <script>
        const table = $('#reportCardsTable').DataTable({
            order: [[2, 'asc']],
            pageLength: 25,
            columnDefs: [
                { targets: [0, 1, -1], orderable: false }
            ]
        });

        const downloadBtn = document.getElementById('downloadSelectedBtn');
        const selectAll = document.getElementById('selectAll');

        // Use the DataTables API (not raw DOM queries) so "select all" and
        // the enable/disable check cover every row across all pages, not
        // just whatever page is currently visible.
        function allCheckboxes() {
            return table.rows().nodes().to$().find('.student-checkbox');
        }

        function refreshButton() {
            const anyChecked = allCheckboxes().toArray().some(cb => cb.checked);
            downloadBtn.disabled = !anyChecked;
        }

        // Delegate the change listener since DataTables re-renders rows
        // when paginating/searching/sorting — checkboxes bound at page load
        // wouldn't exist yet for rows on other pages.
        $('#reportCardsTable tbody').on('change', '.student-checkbox', refreshButton);

        selectAll.addEventListener('change', function () {
            allCheckboxes().each(function () { this.checked = selectAll.checked; });
            refreshButton();
        });
    </script>
@endpush
