@extends('layouts.app')
@section('title', 'Classify Pathways')

@section('content')
    <div class="mb-3">
        <h1 class="h4 mb-1">Classify Pathways \u{2192} Promote to {{ $nextGradeLevel->name }}</h1>
        <p class="text-muted mb-0">Each student needs a pathway assigned once, here - it carries forward automatically through {{ $nextGradeLevel->name }} and beyond.</p>
    </div>

    @if(! $window['open'])
        <div class="alert alert-danger"><i class="bi bi-lock me-1"></i> {{ $window['reason'] }}</div>
    @endif

    @if($ineligible->isNotEmpty())
        <div class="alert alert-warning">
            <strong>{{ $ineligible->count() }} student(s)</strong> are excluded below - incomplete term results:
            <ul class="mb-0 mt-2">
                @foreach($ineligible as $e)
                    <li>{{ $e->student->full_name }} - missing Term {{ implode(', ', $e->missing_terms) }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('curriculum.progression.classify-pathways.store', $gradeLevel->id) }}">
        @csrf
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body">
                <label class="form-label">New Academic Year <span class="text-danger">*</span></label>
                <input type="text" name="new_academic_year" class="form-control" style="max-width:200px;" value="{{ old('new_academic_year', date('Y') + 1) }}" required>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-sm table-striped fs-sm w-100">
                        <thead><tr><th>Student</th><th>Admission No.</th><th style="width:280px;">Pathway <span class="text-danger">*</span></th></tr></thead>
                        <tbody>
                        @forelse($eligible as $enrollment)
                            <tr>
                                <td>{{ $enrollment->student->full_name }}</td>
                                <td>{{ $enrollment->student->userID ?: '-' }}</td>
                                <td>
                                    <input type="hidden" name="classifications[{{ $loop->index }}][enrollment_id]" value="{{ $enrollment->id }}">
                                    <select name="classifications[{{ $loop->index }}][pathway_id]" class="form-select form-select-sm" required>
                                        <option value="">Select pathway</option>
                                        @foreach($pathways as $pathway)
                                            <option value="{{ $pathway->id }}">{{ $pathway->name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center text-muted py-3">No students are currently eligible for classification.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($eligible->isNotEmpty() && $window['open'])
                <div class="card-footer bg-white border-0 p-4 d-flex justify-content-end">
                    <button type="submit" class="btn btn-sm btn-primary px-4" onclick="return confirm('Classify and promote {{ $eligible->count() }} student(s)? This cannot be easily undone.')">
                        Classify &amp; Promote All
                    </button>
                </div>
            @endif
        </div>
    </form>
@endsection
