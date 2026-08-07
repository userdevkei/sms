@extends('layouts.app')
@section('title', 'Academic Terms')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <div>
            <h1 class="h4 mb-1">Academic Terms</h1>
            <p class="text-muted mb-0">Term dates drive the progression window — students can only be promoted between the end of Term 3 and the start of the next year's Term 1.</p>
        </div>
        @can('curriculum.manage')
            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addTermModal">
                <i class="bi bi-plus-lg me-1"></i> Add / Update Term
            </button>
        @endcan
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead><tr><th>#</th><th>Academic Year</th><th>Term</th><th>Start Date</th><th>End Date</th><th class="text-end">Actions</th></tr></thead>
                    <tbody>
                    @forelse($terms as $term)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $term->academic_year }}</td>
                            <td>Term {{ $term->term_number }}</td>
                            <td>{{ $term->start_date->format('d M Y') }}</td>
                            <td>{{ $term->end_date->format('d M Y') }}</td>
                            <td class="text-end">
                                @can('curriculum.manage')
                                    <button type="button" class="btn btn-sm btn-outline-danger btn-delete-term" data-url="{{ route('curriculum.academic-terms.destroy', $term->id) }}"><i class="bi bi-trash"></i></button>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-3">No terms defined yet — progression will stay blocked until these exist.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @can('curriculum.manage')
        <div class="modal fade" id="addTermModal" tabindex="-1">
            <div class="modal-dialog">
                <form method="POST" action="{{ route('curriculum.academic-terms.store') }}">
                    @csrf
                    <div class="modal-content">
                        <div class="modal-header"><h5 class="modal-title">Add / Update Term</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                        <div class="modal-body">
                            @if($errors->any())<div class="alert alert-danger small">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif
                            <div class="mb-2"><label class="form-label">Academic Year <span class="text-danger">*</span></label><input type="text" name="academic_year" class="form-control" placeholder="e.g. 2026" required></div>
                            <div class="mb-2"><label class="form-label">Term Number <span class="text-danger">*</span></label>
                                <select name="term_number" class="form-select" required><option value="1">Term 1</option><option value="2">Term 2</option><option value="3">Term 3</option></select>
                            </div>
                            <div class="row g-2">
                                <div class="col-6"><label class="form-label">Start Date <span class="text-danger">*</span></label><input type="date" name="start_date" class="form-control" required></div>
                                <div class="col-6"><label class="form-label">End Date <span class="text-danger">*</span></label><input type="date" name="end_date" class="form-control" required></div>
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
    <script>
        document.querySelectorAll('.btn-delete-term').forEach(btn => {
            btn.addEventListener('click', function () {
                if (!confirm('Delete this term?')) return;
                fetch(this.dataset.url, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' } })
                    .then(r => r.json()).then(res => res.success ? location.reload() : alert(res.message));
            });
        });
    </script>
@endpush
