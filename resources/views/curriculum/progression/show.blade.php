@extends('layouts.app')
@section('title', $gradeLevel->name . ' - Progression')

@section('content')
    <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
            <h1 class="h4 mb-1">{{ $gradeLevel->name }} - Progression</h1>
            <p class="text-muted mb-0">
                Academic Year {{ $academicYear }}.
                @if($nextGradeLevel) Default outcome: promote to <strong>{{ $nextGradeLevel->name }}</strong>. @else Default outcome: graduate. @endif
            </p>
        </div>
        <a href="{{ route('curriculum.progression.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Back</a>
    </div>

    @if(! $window['open'])
        <div class="alert alert-danger"><i class="bi bi-lock me-1"></i> <strong>Progression window closed:</strong> {{ $window['reason'] }}</div>
    @endif

    @if($ineligible->isNotEmpty())
        <div class="alert alert-warning">
            <strong>{{ $ineligible->count() }} student(s)</strong> have incomplete term results and are excluded from the standard path:
            <ul class="mb-0 mt-2">
                @foreach($ineligible as $e)
                    <li>
                        {{ $e->student->full_name }} - missing Term {{ implode(', ', $e->missing_terms) }}
                        <a href="{{ route('curriculum.term-results.edit', $gradeLevel->id) }}" class="ms-1">Mark results complete</a>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h6 class="mb-1">{{ $eligible->count() }} student(s) ready for the standard path</h6>
                <p class="text-muted small mb-0">This is the expected outcome for nearly every student under CBET.</p>
            </div>
            <div class="d-flex gap-2">
                @can('curriculum.manage')
                    <a href="{{ route('curriculum.progression.exceptions.create', $gradeLevel->id) }}" class="btn btn-sm btn-outline-warning">
                        <i class="bi bi-flag me-1"></i> Flag an Exception
                    </a>
                @endcan
                @can('progression.initiate')
                    @if($enteringSeniorSecondary)
                        <a href="{{ route('curriculum.progression.classify-pathways.create', $gradeLevel->id) }}" class="btn btn-sm btn-primary">
                            <i class="bi bi-signpost-split me-1"></i> Classify Pathways &amp; Promote
                        </a>
                    @else
                        <form method="POST" action="{{ route('curriculum.progression.promote-all', $gradeLevel->id) }}"
                              onsubmit="return confirm('{{ $nextGradeLevel ? 'Promote' : 'Graduate' }} all {{ $eligible->count() }} student(s)?')">
                            @csrf
                            <input type="hidden" name="new_academic_year" value="{{ (int) $academicYear + 1 }}">
                            <button type="submit" class="btn btn-sm btn-primary" {{ !$window['open'] || $eligible->isEmpty() ? 'disabled' : '' }}>
                                <i class="bi bi-check2-circle me-1"></i>
                                {{ $nextGradeLevel ? "Promote All to {$nextGradeLevel->name}" : 'Graduate All Students' }}
                                @if($carryingPathwayForward)<span class="badge bg-light text-primary ms-1">Pathway carries forward</span>@endif
                            </button>
                        </form>
                    @endif
                @endcan
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <h6 class="text-uppercase text-muted small mb-3">Students in the Standard Path</h6>
            @if($eligible->isEmpty())
                <div class="text-center text-muted py-4">No students in the standard path.</div>
            @else
                <div class="table-responsive">
                    <table id="eligibleTable" class="table table-sm align-middle w-100 table-striped">
                        <thead>
                        <tr>
                            <th>#</th><th>Admission No.</th><th>Student Name</th><th>Current Grade</th><th>Stream</th>
                            @if($carryingPathwayForward)<th>Current Pathway</th>@endif
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($eligible as $enrollment)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $enrollment->student->userID ?: '-' }}</td>
                                <td>{{ $enrollment->student->full_name }}</td>
                                <td>{{ $enrollment->gradeLevel?->name }}</td>
                                <td>{{ $enrollment->stream?->name ?? '-' }}</td>
                                @if($carryingPathwayForward)<td>{{ $enrollment->pathway?->name ?? '-' }}</td>@endif
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@endsection

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
@endpush
@push('scripts')
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
    <script>
        if (document.getElementById('eligibleTable')) {
            $('#eligibleTable').DataTable({
                order: [[0, 'asc']],
                pageLength: 25,
                columnDefs: [{ targets: 0, orderable: false }]
            });
        }
    </script>
@endpush
