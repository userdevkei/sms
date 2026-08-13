@extends('layouts.app')
@section('title', 'Other Charge Types')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <div>
            <h1 class="h4 mb-1">Other Charge Types</h1>
            <p class="text-muted mb-0">Categories used when adding one-off charges (e.g. Trip Fee, Uniform, Exam Fee).</p>
        </div>
        @can('other_charges.manage')
            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#typeModal" onclick="openCreateModal()">
                <i class="bi bi-plus-lg me-1"></i> Add Type
            </button>
        @endcan
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table id="typesTable" class="table table-hover table-sm table-striped fs-sm w-100">
                    <thead>
                    <tr><th>#</th>
                        <th>Name</th><th>Description</th><th>Status</th>
                        @can('other_charges.manage')<th class="text-end">Actions</th>@endcan
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($types as $type)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $type->name }}</td>
                            <td class="text-muted">{{ $type->description ?: '—' }}</td>
                            <td><span class="badge {{ $type->status === 'active' ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }} text-capitalize">{{ $type->status }}</span></td>
                            @can('other_charges.manage')
                                <td class="text-end">
                                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick='openEditModal(@json($type))'><i class="bi bi-pencil"></i></button>
                                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteType('{{ $type->id }}')"><i class="bi bi-trash"></i></button>
                                </td>
                            @endcan
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="typeModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="typeForm" method="POST" action="{{ route('finance.other-charge-types.store') }}">
                    @csrf
                    <div id="methodField"></div>
                    <div class="modal-header">
                        <h5 class="modal-title" id="typeModalLabel">Add Charge Type</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="typeName" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" id="typeDescription" rows="2" class="form-control"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status <span class="text-danger">*</span></label>
                            <select name="status" id="typeStatus" class="form-select" required>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-sm btn-primary">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('styles')<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">@endpush
@push('scripts')
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>

    <script>
        $('#typesTable').DataTable({ order: [[0, 'asc']], pageLength: 25 });

        const storeUrl = @json(route('finance.other-charge-types.store'));
        {{--const updateUrlTemplate = @json(route('finance.other-charge-types.update', ['otherChargeType' => '__ID__']));--}}
        {{--const deleteUrlTemplate = @json(route('finance.other-charge-types.destroy', ['otherChargeType' => '__ID__']));--}}
        const updateUrlTemplate = @json(route('finance.other-charge-types.update', ['other_charge_type' => '__ID__']));
        const deleteUrlTemplate = @json(route('finance.other-charge-types.destroy', ['other_charge_type' => '__ID__']));

        function openCreateModal() {
            document.getElementById('typeModalLabel').textContent = 'Add Charge Type';
            document.getElementById('typeForm').action = storeUrl;
            document.getElementById('methodField').innerHTML = '';
            document.getElementById('typeName').value = '';
            document.getElementById('typeDescription').value = '';
            document.getElementById('typeStatus').value = 'active';
        }

        function openEditModal(type) {
            document.getElementById('typeModalLabel').textContent = 'Edit Charge Type';
            document.getElementById('typeForm').action = updateUrlTemplate.replace('__ID__', type.id);
            document.getElementById('methodField').innerHTML = '@method('PATCH')';
            document.getElementById('typeName').value = type.name;
            document.getElementById('typeDescription').value = type.description ?? '';
            document.getElementById('typeStatus').value = type.status;
            new bootstrap.Modal(document.getElementById('typeModal')).show();
        }

        function deleteType(id) {
            if (! confirm('Delete this charge type? This only works if no charges have been recorded against it.')) return;

            fetch(deleteUrlTemplate.replace('__ID__', id), {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': @json(csrf_token()), 'Accept': 'application/json' }
            })
                .then(r => r.json())
                .then(data => { data.success ? location.reload() : alert(data.message); });
        }
    </script>
@endpush
