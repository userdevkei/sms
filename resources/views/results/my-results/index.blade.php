@extends('layouts.app')
@section('title', 'My Results')

@section('content')
    <div class="mb-3">
        <h1 class="h4 mb-1">My Results</h1>
        <p class="text-muted mb-0">View by assessment, term, or full year.</p>
    </div>

    <ul class="nav nav-tabs mb-3">
        <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#assessment-pane">By Assessment</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#term-pane">By Term</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#year-pane">By Year</button></li>
    </ul>

    <div class="tab-content">
        {{-- ASSESSMENT --}}
        <div class="tab-pane fade show active" id="assessment-pane">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="assessmentResultsTable" class="table table-sm align-middle w-100">
                            <thead>
                            <tr><th></th><th>#</th><th>Assessment</th><th>Term</th><th>Date</th><th class="text-end">PDF</th></tr>
                            </thead>
                            <tbody>
                            @foreach($assessmentGroups as $g)
                                <tr>
                                    <td><button class="btn btn-sm btn-link p-0 expand-btn" data-children='@json($g['subjects'])' data-cols="subject,type,score,grade"><i class="bi bi-plus-square"></i></button></td>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $g['name'] }}</td>
                                    <td>{{ $g['term_label'] ?? '—' }}</td>
                                    <td>{{ $g['date_label'] }}</td>
                                    <td class="text-end">
                                        @if($g['pdf_url'])
                                            <a href="{{ $g['pdf_url'] }}" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="bi bi-file-earmark-pdf"></i></a>
                                        @else
                                            <span class="text-muted small">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- TERM --}}
        <div class="tab-pane fade" id="term-pane">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="termResultsTable" class="table table-sm align-middle w-100">
                            <thead>
                            <tr><th></th><th>#</th><th>Term</th><th class="text-end">Average</th><th class="text-end">Grade</th><th class="text-end">Position</th><th class="text-end">PDF</th></tr>
                            </thead>
                            <tbody>
                            @foreach($termResults as $r)
                                <tr>
                                    <td><button class="btn btn-sm btn-link p-0 expand-btn" data-children='@json($r['subjects'])' data-cols="subject,average_score,letter_grade,competency_level"><i class="bi bi-plus-square"></i></button></td>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $r['term_label'] }}</td>
                                    <td class="text-end">{{ number_format($r['average_score'], 1) }}</td>
                                    <td class="text-end">{{ $r['grade'] }}</td>
                                    <td class="text-end">{{ $r['position_label'] }}</td>
                                    <td class="text-end">
                                        <a href="{{ $r['pdf_url'] }}" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="bi bi-file-earmark-pdf"></i></a>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- YEAR --}}
        <div class="tab-pane fade" id="year-pane">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    @if($yearResults->isEmpty())
                        <p class="text-muted mb-0">
                            @if(! $currentEnrollment) No active enrollment on record. @else No published year results yet. @endif
                        </p>
                    @else
                        <div class="table-responsive">
                            <table id="yearResultsTable" class="table table-sm align-middle w-100">
                                <thead>
                                <tr><th></th><th>#</th><th>Grade Level</th><th>Year</th><th class="text-end">Average</th><th class="text-end">Grade</th><th class="text-end">Position</th><th class="text-end">PDF</th></tr>
                                </thead>
                                <tbody>
                                @foreach($yearResults as $r)
                                    @php $isCurrent = $currentYearResult && $r['enrollment']->id === $currentYearResult['enrollment']->id; @endphp
                                    <tr class="{{ $isCurrent ? 'table-primary' : '' }}">
{{--                                        <td><button class="btn btn-sm btn-link p-0 expand-btn" data-children='@json($r['subjects'])' data-cols="subject,average,letter_grade"><i class="bi bi-plus-square"></i></button></td>--}}
                                        <td><button class="btn btn-sm btn-link p-0 expand-btn-year" data-children='@json($r['subjects'])'><i class="bi bi-plus-square"></i></button></td>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>
                                            {{ $r['enrollment']->gradeLevel->name ?? '—' }}
                                            @if($isCurrent) <span class="badge bg-primary ms-1">Current</span> @endif
                                        </td>
                                        <td>{{ $r['enrollment']->academic_year }}</td>
                                        <td class="text-end">{{ $r['yearly_average'] ?? '—' }}</td>
                                        <td class="text-end">{{ $r['grade'] }}</td>
                                        <td class="text-end">{{ $r['yearly_position'] ?? '-' }} / {{ $r['yearly_size'] ?? '-' }}</td>
                                        <td class="text-end">
                                            <a href="{{ $r['pdf_url'] }}" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="bi bi-file-earmark-pdf"></i></a>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">@endpush
@push('scripts')
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
    <script>
        // Column label lookup used to render the child (subject) table's headers.
        const COL_LABELS = {
            subject: 'Subject', type: 'Type', score: 'Score', grade: 'Grade',
            average_score: 'Average', average: 'Average', letter_grade: 'Grade', competency_level: 'Competency',
        };

        function formatChild(children, cols) {
            if (!children || !children.length) {
                return '<div class="p-2 text-muted small">No subject breakdown available.</div>';
            }
            let head = cols.map(c => `<th>${COL_LABELS[c] ?? c}</th>`).join('');
            let rows = children.map(row => {
                let cells = cols.map(c => {
                    let val = row[c] ?? '—';
                    if (c === 'score') val = (row.score ?? '—') + ' / ' + (row.max_score ?? '—');
                    return `<td>${val}</td>`;
                }).join('');
                return `<tr>${cells}</tr>`;
            }).join('');
            return `<div class="p-2 ps-4">
                <table class="table table-sm table-borderless mb-0">
                    <thead><tr>${head}</tr></thead>
                    <tbody>${rows}</tbody>
                </table>
            </div>`;
        }

        function initExpandableTable(selector, orderCol, orderDir) {
            const table = $(selector).DataTable({
                order: [[orderCol, orderDir]],
                pageLength: 25,
                columnDefs: [
                    { targets: 0, orderable: false, className: 'text-center', width: '30px' },
                    { targets: 1, orderable: false, width: '40px' },
                    { targets: -1, orderable: false },
                ],
            });

            $(selector + ' tbody').on('click', '.expand-btn', function () {
                const btn = $(this);
                const tr = btn.closest('tr');
                const row = table.row(tr);
                const icon = btn.find('i');

                if (row.child.isShown()) {
                    row.child.hide();
                    tr.removeClass('shown');
                    icon.removeClass('bi-dash-square').addClass('bi-plus-square');
                } else {
                    const children = btn.data('children');
                    const cols = btn.data('cols').split(',');
                    row.child(formatChild(children, cols)).show();
                    tr.addClass('shown');
                    icon.removeClass('bi-plus-square').addClass('bi-dash-square');
                }
            });

            return table;
        }

        function formatYearChild(subjects) {
            if (!subjects || !subjects.length) {
                return '<div class="p-2 text-muted small">No subject breakdown available.</div>';
            }

            let rows = subjects.map((s, i) => {
                const cell = (t) => {
                    if (!t || t.average_score === null || t.average_score === undefined) return '—';
                    const grade = t.letter_grade ?? t.competency_level;
                    return grade ? `${t.average_score} <span class="text-muted">(${grade})</span>` : t.average_score;
                };

                const yearCell = (s.year_average !== null && s.year_average !== undefined)
                    ? `${s.year_average} <span class="text-muted">(${s.year_grade ?? '—'})</span>`
                    : '—';

                return `<tr>
            <td>${i + 1}</td>
            <td>${s.name ?? '—'}</td>
            <td>${cell(s.terms?.T1)}</td>
            <td>${cell(s.terms?.T2)}</td>
            <td>${cell(s.terms?.T3)}</td>
            <td><strong>${yearCell}</strong></td>
            <td>${s.position ?? '—'}</td>
            <td>${s.remarks ?? '—'}</td>
        </tr>`;
            }).join('');

            return `<div class="p-2 ps-4">
        <table class="table table-sm table-borderless mb-0">
            <thead><tr><th>#</th><th>Subject</th><th>Term 1</th><th>Term 2</th><th>Term 3</th><th>Year Avg</th><th>Position</th><th>Remarks</th></tr></thead>
            <tbody>${rows}</tbody>
        </table>
    </div>`;
        }

        function initYearTable(selector, orderCol, orderDir) {
            const table = $(selector).DataTable({
                order: [[orderCol, orderDir]],
                pageLength: 25,
                columnDefs: [
                    { targets: 0, orderable: false, className: 'text-center', width: '30px' },
                    { targets: 1, orderable: false, width: '40px' },
                    { targets: -1, orderable: false },
                ],
            });

            $(selector + ' tbody').on('click', '.expand-btn-year', function () {
                const btn = $(this);
                const tr = btn.closest('tr');
                const row = table.row(tr);
                const icon = btn.find('i');

                if (row.child.isShown()) {
                    row.child.hide();
                    tr.removeClass('shown');
                    icon.removeClass('bi-dash-square').addClass('bi-plus-square');
                } else {
                    row.child(formatYearChild(btn.data('children'))).show();
                    tr.addClass('shown');
                    icon.removeClass('bi-plus-square').addClass('bi-dash-square');
                }
            });

            return table;
        }

        initExpandableTable('#assessmentResultsTable', 4, 'desc');
        initExpandableTable('#termResultsTable', 2, 'desc');
        // initExpandableTable('#yearResultsTable', 3, 'desc');
        initYearTable('#yearResultsTable', 3, 'desc');
    </script>
@endpush
