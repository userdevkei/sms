@extends('layouts.app')
@section('title', 'Timetables')

@push('styles')
    <style>
        .status-pill { padding: 0.2rem 0.65rem; border-radius: 3px; font-size: 0.78rem; }
        .status-pill.draft { background: #E9EAEC; color: #4B5563; }
        .status-pill.approved { background: #FDF1DA; color: #8A5A00; }
        .status-pill.published { background: #DCF3E8; color: #0F5C4A; }
    </style>
@endpush

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">Timetables</h1>
        @can('timetables.manage')
            <a href="{{ route('timetables.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg me-1"></i> New Timetable
            </a>
        @endcan
    </div>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead>
                <tr>
                    <th>Name</th>
                    <th>Term</th>
                    <th>Grades</th>
                    <th>Status</th>
                    <th>Generated</th>
                    <th class="text-end">Actions</th>
                </tr>
                </thead>
                <tbody>
                @forelse($timetables as $timetable)
                    <tr>
                        <td>{{ $timetable->name }}</td>
                        <td>Term {{ $timetable->term }}, {{ $timetable->academic_year }}</td>
                        <td class="small text-muted">{{ $timetable->gradeLevels->pluck('name')->join(', ') }}</td>
                        <td><span class="status-pill {{ $timetable->status }}">{{ ucfirst($timetable->status) }}</span></td>
                        <td class="small text-muted">
                            {{ $timetable->generated_at?->format('d M Y, H:i') ?? '—' }}
                        </td>
                        <td class="text-end">
                            <a href="{{ route('timetables.preview', $timetable->id) }}" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-eye me-1"></i> Preview
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">No timetables created yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $timetables->links() }}
    </div>
@endsection
