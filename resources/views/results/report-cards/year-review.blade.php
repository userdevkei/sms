@extends('layouts.app')
@section('title', 'Year Report - ' . $academicYear)

@section('content')
    <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
            <h1 class="h4 mb-1">Year Report - {{ $academicYear }}</h1>
            <p class="text-muted mb-0">{{ $stream->full_name }}</p>
        </div>
        <a href="{{ route('results.report-cards.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Back</a>
    </div>

    <form method="GET" action="{{ route('results.report-cards.year-pdf-bulk', [$stream->id, $academicYear]) }}" id="bulkForm" target="_blank">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <input type="checkbox" id="selectAll" class="form-check-input me-1"> <label for="selectAll" class="small">Select all</label>
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-sm btn-outline-primary" id="downloadSelectedBtn" disabled>
                        <i class="bi bi-download me-1"></i> Download Selected
                    </button>
                    <a href="{{ route('results.report-cards.year-pdf-bulk', [$stream->id, $academicYear]) }}" class="btn btn-sm btn-primary" target="_blank">
                        <i class="bi bi-download me-1"></i> Download All
                    </a>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table id="yearReportTable" class="table table-hover table-sm table-striped fs-sm w-100">
                        <thead>
                        <tr>
                            <th></th>
                            <th>#</th>
                            <th>Student NO.</th>
                            <th>Student Name</th>
                            <th class="text-end">T1 Avg</th>
                            <th class="text-end">T2 Avg</th>
                            <th class="text-end">T3 Avg</th>
                            <th class="text-end">Yearly Avg</th>
                            <th class="text-end">Position</th>
                            <th class="text-end">PDF</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($students as $row)
                            <tr>
                                <td>
                                    @if($row['yearly_average'] !== null)
                                        <input type="checkbox" name="enrollments[]" value="{{ $row['enrollment']->id }}" class="form-check-input student-checkbox">
                                    @endif
                                </td>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $row['enrollment']->student->userID ?? '-' }}</td>
                                <td>{{ $row['enrollment']->student->full_name ?? '-' }}</td>
                                <td class="text-end" data-order="{{ $row['terms']['T1']['average'] ?? -1 }}">{{ $row['terms']['T1']['average'] ?? '-' }}</td>
                                <td class="text-end" data-order="{{ $row['terms']['T2']['average'] ?? -1 }}">{{ $row['terms']['T2']['average'] ?? '-' }}</td>
                                <td class="text-end" data-order="{{ $row['terms']['T3']['average'] ?? -1 }}">{{ $row['terms']['T3']['average'] ?? '-' }}</td>
                                <td class="text-end" data-order="{{ $row['yearly_average'] ?? -1 }}">{{ $row['yearly_average'] ?? '-' }}</td>
                                <td class="text-end" data-order="{{ $row['yearly_position'] ?? 999999 }}">{{ $row['yearly_position'] ? $row['yearly_position'] . ' / ' . $row['yearly_size'] : '-' }}</td>
                                <td class="text-end">
                                    @if($row['yearly_average'] !== null)
                                        <a href="{{ route('results.report-cards.year-pdf', [$stream->id, $academicYear, $row['enrollment']->id]) }}"
                                           class="btn btn-sm btn-outline-secondary" target="_blank">
                                            <i class="bi bi-file-earmark-pdf"></i>
                                        </a>
                                    @else
                                        <span class="text-muted small">No published terms</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="text-center text-muted py-3">No students found.</td></tr>
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
        const table = $('#yearReportTable').DataTable({
            order: [[8, 'asc']],
            pageLength: 25,
            columnDefs: [
                { targets: [0, -1], orderable: false }
            ]
        });

        const downloadBtn = document.getElementById('downloadSelectedBtn');
        const selectAll = document.getElementById('selectAll');

        // DataTables API, not raw DOM queries, so "select all" and the
        // enable/disable check cover every row across all pages.
        function allCheckboxes() {
            return table.rows().nodes().to$().find('.student-checkbox');
        }

        function refreshButton() {
            downloadBtn.disabled = !allCheckboxes().toArray().some(cb => cb.checked);
        }

        $('#yearReportTable tbody').on('change', '.student-checkbox', refreshButton);

        selectAll.addEventListener('change', function () {
            allCheckboxes().each(function () { this.checked = selectAll.checked; });
            refreshButton();
        });
    </script>
@endpush
