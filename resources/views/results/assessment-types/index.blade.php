@extends('layouts.app')
@section('title', 'Assessment Types')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <div>
            <h1 class="h4 mb-1">Assessment Types</h1>
            <p class="text-muted mb-0">The kinds of assessment used under CBET - formative, summative, practical, and more.</p>
        </div>
        @can('curriculum.manage')
            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addTypeModal">
                <i class="bi bi-plus-lg me-1"></i> Add Assessment Type
            </button>
        @endcan
    </div>

    <x-results-tabs active="assessment-types" />

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table id="assessmentTypesTable" class="table table-hover table-sm table-striped fs-sm w-100">
                    <thead>
                    <tr>
                        <th>#</th><th>Name</th><th>Scoring Mode</th>
                        <th>Default Max Score</th><th>Status</th><th class="text-end">Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($assessmentTypes as $type)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td class="fw-semibold">{{ $type->name }}</td>
                            <td><span class="badge bg-{{ $type->scoring_mode === 'score' ? 'primary' : 'info' }}-subtle text-{{ $type->scoring_mode === 'score' ? 'primary' : 'info' }} text-capitalize">{{ $type->scoring_mode }}</span></td>
                            <td>{{ $type->default_max_score ?? '-' }}</td>
                            <td><span class="badge bg-{{ $type->status === 'active' ? 'success' : 'secondary' }}-subtle text-{{ $type->status === 'active' ? 'success' : 'secondary' }} text-capitalize">{{ $type->status }}</span></td>
                            <td class="text-end">
                                @can('curriculum.manage')
                                    <button type="button" class="btn btn-sm btn-outline-primary me-1 btn-edit-type"
                                            data-id="{{ $type->id }}" data-name="{{ $type->name }}" data-mode="{{ $type->scoring_mode }}"
                                            data-max="{{ $type->default_max_score }}" data-description="{{ $type->description }}"
                                            data-status="{{ $type->status }}" data-url="{{ route('results.assessment-types.update', $type->id) }}">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-danger btn-delete-type" data-url="{{ route('results.assessment-types.destroy', $type->id) }}">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-3">No assessment types defined yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @can('curriculum.manage')
        <div class="modal fade" id="addTypeModal" tabindex="-1">
            <div class="modal-dialog">
                <form method="POST" action="{{ route('results.assessment-types.store') }}">
                    @csrf
                    <div class="modal-content">
                        <div class="modal-header"><h5 class="modal-title">Add Assessment Type</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                        <div class="modal-body">
                            @if($errors->any())<div class="alert alert-danger small">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif
                            <div class="mb-2"><label class="form-label">Name <span class="text-danger">*</span></label><input type="text" name="name" class="form-control" placeholder="e.g. Mid-Term CAT" required></div>
                            <div class="mb-2"><label class="form-label">Scoring Mode <span class="text-danger">*</span></label>
                                <select name="scoring_mode" class="form-select scoring-mode-select" required>
                                    <option value="score">Numeric Score</option>
                                    <option value="competency">Competency Rating (EE/ME/AE/BE)</option>
                                </select>
                            </div>
                            <div class="mb-2 max-score-field"><label class="form-label">Default Max Score</label><input type="number" name="default_max_score" class="form-control" min="1"></div>
                            <div class="mb-2"><label class="form-label">Description</label><textarea name="description" rows="2" class="form-control"></textarea></div>
                            <div class="mb-0"><label class="form-label">Status</label><select name="status" class="form-select"><option value="active" selected>Active</option><option value="inactive">Inactive</option></select></div>
                        </div>
                        <div class="modal-footer"><button type="submit" class="btn btn-primary">Save</button></div>
                    </div>
                </form>
            </div>
        </div>

        <div class="modal fade" id="editTypeModal" tabindex="-1">
            <div class="modal-dialog">
                <form method="POST" id="editTypeForm" action="">
                    @csrf @method('PUT')
                    <div class="modal-content">
                        <div class="modal-header"><h5 class="modal-title">Edit Assessment Type</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                        <div class="modal-body">
                            <div class="mb-2"><label class="form-label">Name <span class="text-danger">*</span></label><input type="text" name="name" id="edit_name" class="form-control" required></div>
                            <div class="mb-2"><label class="form-label">Scoring Mode <span class="text-danger">*</span></label>
                                <select name="scoring_mode" id="edit_scoring_mode" class="form-select scoring-mode-select" required>
                                    <option value="score">Numeric Score</option>
                                    <option value="competency">Competency Rating (EE/ME/AE/BE)</option>
                                </select>
                            </div>
                            <div class="mb-2 max-score-field"><label class="form-label">Default Max Score</label><input type="number" name="default_max_score" id="edit_max" class="form-control" min="1"></div>
                            <div class="mb-2"><label class="form-label">Description</label><textarea name="description" id="edit_description" rows="2" class="form-control"></textarea></div>
                            <div class="mb-0"><label class="form-label">Status</label><select name="status" id="edit_status" class="form-select"><option value="active">Active</option><option value="inactive">Inactive</option></select></div>
                        </div>
                        <div class="modal-footer"><button type="submit" class="btn btn-primary">Update</button></div>
                    </div>
                </form>
            </div>
        </div>
    @endcan
@endsection

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
@endpush
@push('scripts')
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $('#assessmentTypesTable').DataTable({
            order: [[1, 'asc']],
            pageLength: 25,
            columnDefs: [{ targets: [0, -1], orderable: false }]
        });

        function toggleMaxScoreField(scope) {
            const select = scope.querySelector('.scoring-mode-select');
            const field = scope.querySelector('.max-score-field');
            field.style.display = select.value === 'score' ? '' : 'none';
        }
        document.querySelectorAll('.scoring-mode-select').forEach(sel => {
            sel.addEventListener('change', () => toggleMaxScoreField(sel.closest('.modal-content')));
        });

        document.querySelectorAll('.btn-edit-type').forEach(btn => {
            btn.addEventListener('click', function () {
                document.getElementById('editTypeForm').action = this.dataset.url;
                document.getElementById('edit_name').value = this.dataset.name;
                document.getElementById('edit_scoring_mode').value = this.dataset.mode;
                document.getElementById('edit_max').value = this.dataset.max || '';
                document.getElementById('edit_description').value = this.dataset.description || '';
                document.getElementById('edit_status').value = this.dataset.status;
                toggleMaxScoreField(document.getElementById('editTypeModal'));
                new bootstrap.Modal(document.getElementById('editTypeModal')).show();
            });
        });

        document.querySelectorAll('.btn-delete-type').forEach(btn => {
            btn.addEventListener('click', function () {
                if (!confirm('Delete this assessment type?')) return;
                fetch(this.dataset.url, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' } })
                    .then(r => r.json()).then(res => res.success ? location.reload() : alert(res.message));
            });
        });
    </script>
@endpush
