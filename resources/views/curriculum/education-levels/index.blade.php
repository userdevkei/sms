@extends('layouts.app')
@section('title', 'Education Levels')
<meta name="csrf-token" content="{{ csrf_token() }}">
@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <div>
            <h1 class="h4 mb-1">Education Levels</h1>
            <p class="text-muted mb-0">The CBET structure: Pre-Primary through Senior Secondary.</p>
        </div>
        @can('curriculum.manage')
            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addLevelModal">
                <i class="bi bi-plus-lg me-1"></i> Add Level
            </button>
        @endcan
    </div>

    <x-curriculum-tabs active="education-levels" />

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover table-sm table-striped fs-sm w-100">
                    <thead>
                    <tr><th>#</th><th>Name</th><th>Code</th><th>Sequence</th><th>Grade Levels</th><th>Status</th><th class="text-end">Actions</th></tr>
                    </thead>
                    <tbody>
                    @forelse($educationLevels as $index => $level)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td class="fw-semibold">{{ $level->name }}</td>
                            <td><span class="badge bg-secondary-subtle text-secondary">{{ $level->code }}</span></td>
                            <td>{{ $level->sequence }}</td>
                            <td>
                                <a href="{{ route('curriculum.grade-levels.index', ['education_level' => $level->id]) }}">
                                    {{ $level->grade_levels_count }} grade(s)
                                </a>
                            </td>
                            <td>
                                <span class="badge bg-{{ $level->status === 'active' ? 'success' : 'secondary' }}-subtle text-{{ $level->status === 'active' ? 'success' : 'secondary' }} text-capitalize">{{ $level->status }}</span>
                            </td>
                            <td class="text-end">
                                @can('curriculum.manage')
                                    <button type="button" class="btn btn-sm btn-outline-primary me-1 btn-edit-level"
                                            data-id="{{ $level->id }}" data-name="{{ $level->name }}" data-code="{{ $level->code }}"
                                            data-sequence="{{ $level->sequence }}" data-description="{{ $level->description }}"
                                            data-status="{{ $level->status }}" data-url="{{ route('curriculum.education-levels.update', $level->id) }}"
                                            title="Edit"><i class="bi bi-pencil"></i></button>
                                    <button type="button" class="btn btn-sm btn-outline-danger btn-delete-level"
                                            data-url="{{ route('curriculum.education-levels.destroy', $level->id) }}" title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-3">No education levels defined yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @can('curriculum.manage')
        <div class="modal fade" id="addLevelModal" tabindex="-1">
            <div class="modal-dialog">
                <form method="POST" action="{{ route('curriculum.education-levels.store') }}">
                    @csrf
                    <div class="modal-content">
                        <div class="modal-header"><h5 class="modal-title">Add Education Level</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                        <div class="modal-body">
                            @if($errors->any() && !old('_edit_id'))
                                <div class="alert alert-danger small">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
                            @endif
                            <div class="mb-2"><label class="form-label">Name <span class="text-danger">*</span></label><input type="text" name="name" class="form-control" placeholder="e.g. Junior Secondary" required></div>
                            <div class="row g-2 mb-2">
                                <div class="col-6"><label class="form-label">Code <span class="text-danger">*</span></label><input type="text" name="code" class="form-control" placeholder="e.g. JS" maxlength="10" required></div>
                                <div class="col-6"><label class="form-label">Sequence <span class="text-danger">*</span></label><input type="number" name="sequence" class="form-control" min="1" required></div>
                            </div>
                            <div class="mb-2"><label class="form-label">Description</label><textarea name="description" rows="2" class="form-control"></textarea></div>
                            <div class="mb-0"><label class="form-label">Status</label>
                                <select name="status" class="form-select"><option value="active" selected>Active</option><option value="inactive">Inactive</option></select>
                            </div>
                        </div>
                        <div class="modal-footer"><button type="submit" class="btn btn-sm btn-primary">Save</button></div>
                    </div>
                </form>
            </div>
        </div>

        <div class="modal fade" id="editLevelModal" tabindex="-1">
            <div class="modal-dialog">
                <form method="POST" id="editLevelForm" action="">
                    @csrf @method('PUT')
                    <div class="modal-content">
                        <div class="modal-header"><h5 class="modal-title">Edit Education Level</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                        <div class="modal-body">
                            <div class="mb-2"><label class="form-label">Name <span class="text-danger">*</span></label><input type="text" name="name" id="edit_name" class="form-control" required></div>
                            <div class="row g-2 mb-2">
                                <div class="col-6"><label class="form-label">Code <span class="text-danger">*</span></label><input type="text" name="code" id="edit_code" class="form-control" maxlength="10" required></div>
                                <div class="col-6"><label class="form-label">Sequence <span class="text-danger">*</span></label><input type="number" name="sequence" id="edit_sequence" class="form-control" min="1" required></div>
                            </div>
                            <div class="mb-2"><label class="form-label">Description</label><textarea name="description" id="edit_description" rows="2" class="form-control"></textarea></div>
                            <div class="mb-0"><label class="form-label">Status</label>
                                <select name="status" id="edit_status" class="form-select"><option value="active">Active</option><option value="inactive">Inactive</option></select>
                            </div>
                        </div>
                        <div class="modal-footer"><button type="submit" class="btn btn-sm btn-primary">Update</button></div>
                    </div>
                </form>
            </div>
        </div>
    @endcan
@endsection

@push('scripts')
    <script>
        document.querySelectorAll('.btn-edit-level').forEach(btn => {
            btn.addEventListener('click', function () {
                document.getElementById('editLevelForm').action = this.dataset.url;
                document.getElementById('edit_name').value = this.dataset.name;
                document.getElementById('edit_code').value = this.dataset.code;
                document.getElementById('edit_sequence').value = this.dataset.sequence;
                document.getElementById('edit_description').value = this.dataset.description || '';
                document.getElementById('edit_status').value = this.dataset.status;
                new bootstrap.Modal(document.getElementById('editLevelModal')).show();
            });
        });

        document.querySelectorAll('.btn-delete-level').forEach(btn => {
            btn.addEventListener('click', function () {
                if (!confirm('Delete this education level?')) return;
                fetch(this.dataset.url, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' }
                }).then(r => r.json()).then(res => res.success ? location.reload() : alert(res.message));
            });
        });
    </script>
@endpush
