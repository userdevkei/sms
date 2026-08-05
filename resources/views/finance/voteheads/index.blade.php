@extends('layouts.app')
@section('title', 'Voteheads')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <div>
            <h1 class="h4 mb-1">Voteheads</h1>
            <p class="text-muted mb-0">The individual fee categories used when building fee structures — Tuition, Activity Fees, Remedial, etc.</p>
        </div>
        @can('fee_structures.manage')
            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addVoteheadModal"><i class="bi bi-plus-lg me-1"></i> Add Votehead</button>
        @endcan
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead><tr><th>#</th><th>Name</th><th>Code</th><th>Category</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
                    <tbody>
                    @forelse($voteheads as $votehead)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td class="fw-semibold">{{ $votehead->name }}</td>
                            <td>{{ $votehead->code ?: '-' }}</td>
                            <td><span class="badge bg-secondary-subtle text-secondary text-capitalize">{{ $votehead->category }}</span></td>
                            <td><span class="badge bg-{{ $votehead->status === 'active' ? 'success' : 'secondary' }}-subtle text-{{ $votehead->status === 'active' ? 'success' : 'secondary' }} text-capitalize">{{ $votehead->status }}</span></td>
                            <td class="text-end">
                                @can('fee_structures.manage')
                                    <button type="button" class="btn btn-sm btn-outline-primary me-1 btn-edit-votehead"
                                            data-id="{{ $votehead->id }}" data-name="{{ $votehead->name }}" data-code="{{ $votehead->code }}"
                                            data-category="{{ $votehead->category }}" data-description="{{ $votehead->description }}" data-status="{{ $votehead->status }}"
                                            data-url="{{ route('finance.voteheads.update', $votehead->id) }}"><i class="bi bi-pencil"></i></button>
                                    <button type="button" class="btn btn-sm btn-outline-danger btn-delete" data-url="{{ route('finance.voteheads.destroy', $votehead->id) }}"><i class="bi bi-trash"></i></button>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-3">No voteheads defined yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @can('fee_structures.manage')
        <div class="modal fade" id="addVoteheadModal" tabindex="-1">
            <div class="modal-dialog">
                <form method="POST" action="{{ route('finance.voteheads.store') }}">
                    @csrf
                    <div class="modal-content">
                        <div class="modal-header"><h5 class="modal-title">Add Votehead</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                        <div class="modal-body">
                            @if($errors->any())<div class="alert alert-danger small">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif
                            <div class="mb-2"><label class="form-label">Name <span class="text-danger">*</span></label><input type="text" name="name" class="form-control" placeholder="e.g. Tuition Fee" required></div>
                            <div class="row g-2 mb-2">
                                <div class="col-6"><label class="form-label">Code</label><input type="text" name="code" class="form-control" placeholder="e.g. TUI"></div>
                                <div class="col-6"><label class="form-label">Category <span class="text-danger">*</span></label>
                                    <select name="category" class="form-select" required>
                                        <option value="tuition">Tuition</option><option value="activity">Activity</option>
                                        <option value="remedial">Remedial</option><option value="examination">Examination</option><option value="other" selected>Other</option>
                                    </select>
                                </div>
                            </div>
                            <div class="mb-2"><label class="form-label">Description</label><textarea name="description" rows="2" class="form-control"></textarea></div>
                            <div class="mb-0"><label class="form-label">Status</label><select name="status" class="form-select"><option value="active" selected>Active</option><option value="inactive">Inactive</option></select></div>
                        </div>
                        <div class="modal-footer"><button type="submit" class="btn btn-sm btn-primary">Save</button></div>
                    </div>
                </form>
            </div>
        </div>

        <div class="modal fade" id="editVoteheadModal" tabindex="-1">
            <div class="modal-dialog">
                <form method="POST" id="editVoteheadForm" action="">
                    @csrf @method('PUT')
                    <div class="modal-content">
                        <div class="modal-header"><h5 class="modal-title">Edit Votehead</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                        <div class="modal-body">
                            <div class="mb-2"><label class="form-label">Name <span class="text-danger">*</span></label><input type="text" name="name" id="edit_v_name" class="form-control" required></div>
                            <div class="row g-2 mb-2">
                                <div class="col-6"><label class="form-label">Code</label><input type="text" name="code" id="edit_v_code" class="form-control"></div>
                                <div class="col-6"><label class="form-label">Category <span class="text-danger">*</span></label>
                                    <select name="category" id="edit_v_category" class="form-select" required>
                                        <option value="tuition">Tuition</option><option value="activity">Activity</option>
                                        <option value="remedial">Remedial</option><option value="examination">Examination</option><option value="other">Other</option>
                                    </select>
                                </div>
                            </div>
                            <div class="mb-2"><label class="form-label">Description</label><textarea name="description" id="edit_v_description" rows="2" class="form-control"></textarea></div>
                            <div class="mb-0"><label class="form-label">Status</label><select name="status" id="edit_v_status" class="form-select"><option value="active">Active</option><option value="inactive">Inactive</option></select></div>
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
        document.querySelectorAll('.btn-edit-votehead').forEach(btn => {
            btn.addEventListener('click', function () {
                document.getElementById('editVoteheadForm').action = this.dataset.url;
                document.getElementById('edit_v_name').value = this.dataset.name;
                document.getElementById('edit_v_code').value = this.dataset.code || '';
                document.getElementById('edit_v_category').value = this.dataset.category;
                document.getElementById('edit_v_description').value = this.dataset.description || '';
                document.getElementById('edit_v_status').value = this.dataset.status;
                new bootstrap.Modal(document.getElementById('editVoteheadModal')).show();
            });
        });
        document.querySelectorAll('.btn-delete').forEach(btn => {
            btn.addEventListener('click', function () {
                if (!confirm('Delete this item?')) return;
                fetch(this.dataset.url, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' } })
                    .then(r => r.json()).then(res => res.success ? location.reload() : alert(res.message));
            });
        });
    </script>
@endpush
