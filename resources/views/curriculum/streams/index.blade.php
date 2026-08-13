@extends('layouts.app')
@section('title', 'Streams / Classes')
<meta name="csrf-token" content="{{ csrf_token() }}">
@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <div>
            <h1 class="h4 mb-1">Streams / Classes</h1>
            <p class="text-muted mb-0">Actual classes within each grade level.</p>
        </div>
        @can('curriculum.manage')
            <a href="{{ route('curriculum.streams.create') }}" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg me-1"></i> Add Stream</a>
        @endcan
    </div>

    <x-curriculum-tabs active="streams" />

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-6 col-md-4">
                    <label class="form-label small text-muted mb-1">Grade Level</label>
                    <select name="grade_level" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">All Grade Levels</option>
                        @foreach($gradeLevels as $grade)
                            <option value="{{ $grade->id }}" @selected(request('grade_level') === $grade->id)>{{ $grade->name }}</option>
                        @endforeach
                    </select>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover table-sm table-striped fs-sm w-100">
                    <thead><tr><th>Class</th><th>Grade Level</th><th>Pathway</th><th>Class Teacher</th><th>Capacity</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
                    <tbody>
                    @forelse($streams as $stream)
                        <tr>
                            <td class="fw-semibold">{{ $stream->full_name }}</td>
                            <td>{{ $stream->gradeLevel->name }}</td>
                            <td>{{ $stream->pathway?->name ?? '—' }}</td>
                            <td>{{ $stream->classTeacher?->full_name ?? '—' }}</td>
                            <td>{{ $stream->capacity ?? '—' }}</td>
                            <td><span class="badge bg-{{ $stream->status === 'active' ? 'success' : 'secondary' }}-subtle text-{{ $stream->status === 'active' ? 'success' : 'secondary' }} text-capitalize">{{ $stream->status }}</span></td>
                            <td class="text-end">
                                @can('curriculum.manage')
                                    <a href="{{ route('curriculum.streams.edit', $stream->id) }}" class="btn btn-sm btn-outline-primary me-1"><i class="bi bi-pencil"></i></a>
                                    <button type="button" class="btn btn-sm btn-outline-danger btn-delete-stream" data-url="{{ route('curriculum.streams.destroy', $stream->id) }}"><i class="bi bi-trash"></i></button>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-3">No streams found.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.querySelectorAll('.btn-delete-stream').forEach(btn => {
            btn.addEventListener('click', function () {
                if (!confirm('Delete this stream?')) return;
                fetch(this.dataset.url, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' }
                }).then(r => r.json()).then(res => res.success ? location.reload() : alert(res.message));
            });
        });
    </script>
@endpush
