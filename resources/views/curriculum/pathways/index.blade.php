@extends('layouts.app')
@section('title', 'Senior Secondary Pathways')
<meta name="csrf-token" content="{{ csrf_token() }}">
@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <div>
            <h1 class="h4 mb-1">Senior Secondary Pathways</h1>
            <p class="text-muted mb-0">STEM, Social Sciences, and Arts & Sports Science learning pathways.</p>
        </div>
        @can('curriculum.manage')
            <a href="{{ route('curriculum.pathways.create') }}" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg me-1"></i> Add Pathway</a>
        @endcan
    </div>

    <x-curriculum-tabs active="pathways" />

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead><tr><th>Name</th><th>Code</th><th>Subjects</th><th>Streams</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
                    <tbody>
                    @forelse($pathways as $pathway)
                        <tr>
                            <td class="fw-semibold">{{ $pathway->name }}</td>
                            <td>{{ $pathway->code ?: '—' }}</td>
                            <td>{{ $pathway->learning_areas_count }} subject(s)</td>
                            <td>{{ $pathway->streams_count }} class(es)</td>
                            <td><span class="badge bg-{{ $pathway->status === 'active' ? 'success' : 'secondary' }}-subtle text-{{ $pathway->status === 'active' ? 'success' : 'secondary' }} text-capitalize">{{ $pathway->status }}</span></td>
                            <td class="text-end">
                                @can('curriculum.manage')
                                    <a href="{{ route('curriculum.pathways.edit', $pathway->id) }}" class="btn btn-sm btn-outline-primary me-1"><i class="bi bi-pencil"></i></a>
                                    <button type="button" class="btn btn-sm btn-outline-danger btn-delete-pathway" data-url="{{ route('curriculum.pathways.destroy', $pathway->id) }}"><i class="bi bi-trash"></i></button>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-3">No pathways defined yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.querySelectorAll('.btn-delete-pathway').forEach(btn => {
            btn.addEventListener('click', function () {
                if (!confirm('Delete this pathway?')) return;
                fetch(this.dataset.url, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' }
                }).then(r => r.json()).then(res => res.success ? location.reload() : alert(res.message));
            });
        });
    </script>
@endpush
