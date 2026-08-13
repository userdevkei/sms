@extends('layouts.app')
@section('title', 'Learning Areas')
<meta name="csrf-token" content="{{ csrf_token() }}">
@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <div>
            <h1 class="h4 mb-1">Learning Areas (Subjects)</h1>
            <p class="text-muted mb-0">Subjects offered, and the grade levels each one applies to.</p>
        </div>
        @can('curriculum.manage')
            <a href="{{ route('curriculum.learning-areas.create') }}" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg me-1"></i> Add Learning Area</a>
        @endcan
    </div>

    <x-curriculum-tabs active="learning-areas" />

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover table-sm table-striped fs-sm w-100">
                    <thead><tr><th>Name</th><th>Code</th><th>Compulsory</th><th>Grade Levels</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
                    <tbody>
                    @forelse($learningAreas as $area)
                        <tr>
                            <td class="fw-semibold">{{ $area->name }}</td>
                            <td>{{ $area->code ?: '—' }}</td>
                            <td>{!! $area->is_compulsory ? '<span class="badge bg-primary-subtle text-primary">Compulsory</span>' : '<span class="badge bg-light text-muted">Elective</span>' !!}</td>
                            <td>{{ $area->grade_levels_count }} grade(s)</td>
                            <td><span class="badge bg-{{ $area->status === 'active' ? 'success' : 'secondary' }}-subtle text-{{ $area->status === 'active' ? 'success' : 'secondary' }} text-capitalize">{{ $area->status }}</span></td>
                            <td class="text-end">
                                @can('curriculum.manage')
                                    <a href="{{ route('curriculum.learning-areas.edit', $area->id) }}" class="btn btn-sm btn-outline-primary me-1"><i class="bi bi-pencil"></i></a>
                                    <button type="button" class="btn btn-sm btn-outline-danger btn-delete-area" data-url="{{ route('curriculum.learning-areas.destroy', $area->id) }}"><i class="bi bi-trash"></i></button>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-3">No learning areas defined yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.querySelectorAll('.btn-delete-area').forEach(btn => {
            btn.addEventListener('click', function () {
                if (!confirm('Delete this learning area?')) return;
                fetch(this.dataset.url, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' }
                }).then(r => r.json()).then(res => res.success ? location.reload() : alert(res.message));
            });
        });
    </script>
@endpush
