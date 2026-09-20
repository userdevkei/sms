@extends('layouts.app')
@section('title', 'Lesson Requirements')
@section('content')
    <h1 class="h4 mb-1">Lesson Requirements</h1>
    <p class="text-muted small mb-3">How many lessons per week each subject gets, per grade. This drives timetable generation.</p>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead>
                <tr><th>Grade</th><th>Education Level</th><th>Subjects Configured</th><th class="text-end">Actions</th></tr>
                </thead>
                <tbody>
                @foreach($gradeLevels as $grade)
                    <tr>
                        <td>{{ $grade->name }}</td>
                        <td class="text-muted small">{{ $grade->educationLevel->name }}</td>
                        <td>
                            @php $count = $configuredCounts[$grade->id] ?? 0; @endphp
                            @if($count > 0)
                                <span class="badge bg-success-subtle text-success border">{{ $count }} configured</span>
                            @else
                                <span class="badge bg-secondary-subtle text-secondary border">Not set up</span>
                            @endif
                        </td>
                        <td class="text-end">
                            @can('curriculum.manage')
                                <a href="{{ route('curriculum.lesson-requirements.edit', $grade->id) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-pencil me-1"></i> Configure
                                </a>
                            @endcan
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
