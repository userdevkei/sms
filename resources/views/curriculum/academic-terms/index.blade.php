@extends('layouts.app')
@section('title', 'Academic Terms')

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
    <style>
        #academicTermsTable_wrapper .dataTables_filter input { border-radius: .375rem; }
        #academicTermsTable_wrapper .dataTables_length select { border-radius: .375rem; }
    </style>
@endpush

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <div>
            <h1 class="h4 mb-1">Academic Terms</h1>
            <p class="text-muted mb-0">Term dates drive the progression window — students can only be promoted between the end of Term 3 and the start of the next year's Term 1.</p>
        </div>
        @can('curriculum.manage')
            <button type="button" class="btn btn-sm btn-primary" id="btnAddTerm" data-bs-toggle="modal" data-bs-target="#termModal">
                <i class="bi bi-plus-lg me-1"></i> Add Term
            </button>
        @endcan
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table id="academicTermsTable" class="table table-hover table-sm table-striped fs-sm w-100">
                    <thead>
                    <tr>
                        <th>#</th>
                        <th>Academic Year</th>
                        <th>Term</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th class="text-end">Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($terms as $term)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $term->academic_year }}</td>
                            <td>Term {{ $term->term_number }}</td>
                            <td data-order="{{ $term->start_date->format('Y-m-d') }}">{{ $term->start_date->format('d M Y') }}</td>
                            <td data-order="{{ $term->end_date->format('Y-m-d') }}">{{ $term->end_date->format('d M Y') }}</td>
                            <td class="text-end">
                                @can('curriculum.manage')
                                    <button type="button"
                                            class="btn btn-sm btn-outline-secondary btn-edit-term"
                                            data-bs-toggle="modal" data-bs-target="#termModal"
                                            data-id="{{ $term->id }}"
                                            data-academic-year="{{ $term->academic_year }}"
                                            data-term-number="{{ $term->term_number }}"
                                            data-start-date="{{ $term->start_date->format('Y-m-d') }}"
                                            data-end-date="{{ $term->end_date->format('Y-m-d') }}"
                                            data-update-url="{{ route('curriculum.academic-terms.update', $term->id) }}">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-danger btn-delete-term" data-url="{{ route('curriculum.academic-terms.destroy', $term->id) }}"><i class="bi bi-trash"></i></button>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-3">No terms defined yet — progression will stay blocked until these exist.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @can('curriculum.manage')
        <div class="modal fade" id="termModal" tabindex="-1">
            <div class="modal-dialog">
                <form method="POST" action="{{ route('curriculum.academic-terms.store') }}" id="termForm">
                    @csrf
                    <input type="hidden" name="_method" id="termFormMethod" value="POST">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="termModalTitle">Add Term</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            @if($errors->any())<div class="alert alert-danger small">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif
                            <div class="mb-2"><label class="form-label">Academic Year <span class="text-danger">*</span></label><input type="text" name="academic_year" id="termAcademicYear" class="form-control" placeholder="e.g. 2026" required></div>
                            <div class="mb-2"><label class="form-label">Term Number <span class="text-danger">*</span></label>
                                <select name="term_number" id="termNumber" class="form-select" required><option value="1">Term 1</option><option value="2">Term 2</option><option value="3">Term 3</option></select>
                            </div>
                            <div class="row g-2">
                                <div class="col-6"><label class="form-label">Start Date <span class="text-danger">*</span></label><input type="date" name="start_date" id="termStartDate" class="form-control" required></div>
                                <div class="col-6"><label class="form-label">End Date <span class="text-danger">*</span></label><input type="date" name="end_date" id="termEndDate" class="form-control" required></div>
                            </div>
                        </div>
                        <div class="modal-footer"><button type="submit" class="btn btn-sm btn-primary">Save</button></div>
                    </div>
                </form>
            </div>
        </div>
    @endcan
@endsection

@push('scripts')
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            $('#academicTermsTable').DataTable({
                order: [[1, 'desc'], [2, 'desc']],
                columnDefs: [
                    { orderable: false, targets: -1 },
                    { className: 'text-end', targets: -1 },
                ],
                language: {
                    search: '',
                    searchPlaceholder: 'Search terms...',
                    emptyTable: "No terms defined yet — progression will stay blocked until these exist.",
                },
                pageLength: 10,
                lengthMenu: [10, 25, 50, 100],
            });

            const form        = document.getElementById('termForm');
            const methodField = document.getElementById('termFormMethod');
            const titleEl     = document.getElementById('termModalTitle');
            const storeUrl    = form.getAttribute('action');

            // Reset modal to "Add" mode whenever it's opened via the Add button
            document.getElementById('btnAddTerm')?.addEventListener('click', function () {
                form.reset();
                form.setAttribute('action', storeUrl);
                methodField.value = 'POST';
                titleEl.textContent = 'Add Term';
            });

            // Populate modal for editing
            $('#academicTermsTable').on('click', '.btn-edit-term', function () {
                const btn = this;
                document.getElementById('termAcademicYear').value = btn.dataset.academicYear;
                document.getElementById('termNumber').value = btn.dataset.termNumber;
                document.getElementById('termStartDate').value = btn.dataset.startDate;
                document.getElementById('termEndDate').value = btn.dataset.endDate;

                form.setAttribute('action', btn.dataset.updateUrl);
                methodField.value = 'PUT';
                titleEl.textContent = `Edit ${btn.dataset.academicYear} — Term ${btn.dataset.termNumber}`;
            });

            $('#academicTermsTable').on('click', '.btn-delete-term', function () {
                if (!confirm('Delete this term?')) return;
                fetch(this.dataset.url, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                })
                    .then(r => r.json())
                    .then(res => res.success ? location.reload() : alert(res.message));
            });
        });
    </script>
@endpush
