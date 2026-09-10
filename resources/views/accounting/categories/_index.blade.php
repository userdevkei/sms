{{-- Included by income-categories/index.blade.php and expense-categories/index.blade.php.
     Expects: $categories, $type ('income'|'expense'), $title --}}
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0">{{ $title }}</h1>
    <a href="{{ route("accounting.{$type}-categories.create") }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i> Add Category
    </a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sm align-middle table-striped fs-sm" id="categoriesTable" style="width:100%">
                <thead>
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Description</th>
                    <th>Transactions</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
                </thead>
                <tbody>
                @forelse($categories as $category)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $category->name }}</td>
                        <td class="text-muted small">{{ $category->description ?: '—' }}</td>
                        <td>{{ $category->transactions_count }}</td>
                        <td>
                            <span class="badge {{ $category->status === 'active' ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }} border">
                                {{ ucfirst($category->status) }}
                            </span>
                        </td>
                        <td class="text-end">
                            <a href="{{ route("accounting.{$type}-categories.edit", $category->id) }}" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <button type="button" class="btn btn-sm btn-outline-danger delete-category"
                                    data-config='{{ json_encode(["id" => $category->id, "name" => $category->name, "url" => route("accounting.{$type}-categories.destroy", $category->id)]) }}'>
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">No categories yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
@endpush

@push('scripts')
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            $('#categoriesTable').DataTable({
                dom: '<"d-flex justify-content-between align-items-center mb-3"lf>rt<"d-flex justify-content-between align-items-center mt-3"ip>',
                columnDefs: [
                    { orderable: false, searchable: false, targets: [0, 5] } // # and Actions columns
                ],
                order: [], // keep default row order instead of auto-sorting by column 0
                language: {
                    search: '',
                    searchPlaceholder: 'Search...',
                    lengthMenu: 'Show _MENU_',
                },
            });

            document.querySelectorAll('.delete-category').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    const cfg = JSON.parse(this.dataset.config);
                    if (!confirm(`Delete category "${cfg.name}"? This cannot be undone.`)) return;

                    fetch(cfg.url, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json',
                        },
                    })
                        .then(r => r.json())
                        .then(res => {
                            if (res.success) { window.location.reload(); }
                            else { alert(res.message); }
                        });
                });
            });
        });
    </script>
@endpush
