@extends('layouts.app')
@section('title', 'Grade Levels')
<meta name="csrf-token" content="{{ csrf_token() }}">

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <div>
            <h1 class="h4 mb-1">Grade Levels</h1>
            <p class="text-muted mb-0">Grades within each education level, in progression order.</p>
        </div>
        @can('curriculum.manage')
            <a href="{{ route('curriculum.grade-levels.create') }}" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg me-1"></i> Add Grade Level</a>
        @endcan
    </div>

    <x-curriculum-tabs active="grade-levels" />

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-6 col-md-4">
                    <label class="form-label small text-muted mb-1">Education Level</label>
                    <select name="education_level" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">All Levels</option>
                        @foreach($educationLevels as $level)
                            <option value="{{ $level->id }}" @selected(request('education_level') === $level->id)>{{ $level->name }}</option>
                        @endforeach
                    </select>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                    <tr><th>Seq</th><th>Grade</th><th>Code</th><th>Education Level</th><th>Subjects</th><th>Streams</th><th>Status</th><th class="text-end">Actions</th></tr>
                    </thead>
                    <tbody>
                    @forelse($gradeLevels as $grade)
                        <tr>
                            <td>{{ $grade->sequence }}</td>
                            <td class="fw-semibold">{{ $grade->name }}</td>
                            <td>{{ $grade->code ?: '—' }}</td>
                            <td>{{ $grade->educationLevel->name }}</td>
                            <td><a href="{{ route('curriculum.learning-areas.index', ['grade_level' => $grade->id]) }}">{{ $grade->learning_areas_count }} subject(s)</a></td>
                            <td><a href="{{ route('curriculum.streams.index', ['grade_level' => $grade->id]) }}">{{ $grade->streams_count }} class(es)</a></td>
                            <td><span class="badge bg-{{ $grade->status === 'active' ? 'success' : 'secondary' }}-subtle text-{{ $grade->status === 'active' ? 'success' : 'secondary' }} text-capitalize">{{ $grade->status }}</span></td>
                            <td class="text-end">
                                @can('curriculum.manage')
                                    <a href="{{ route('curriculum.grade-levels.edit', $grade->id) }}" class="btn btn-sm btn-outline-primary me-1"><i class="bi bi-pencil"></i></a>
                                    <button type="button" class="btn btn-sm btn-outline-danger btn-delete-grade" data-url="{{ route('curriculum.grade-levels.destroy', $grade->id) }}"><i class="bi bi-trash"></i></button>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted py-3">No grade levels found.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.querySelectorAll('.btn-delete-grade').forEach(btn => {
            btn.addEventListener('click', function () {
                if (!confirm('Delete this grade level?')) return;
                fetch(this.dataset.url, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' }
                }).then(r => r.json()).then(res => res.success ? location.reload() : alert(res.message));
            });
        });
    </script>
@endpush
