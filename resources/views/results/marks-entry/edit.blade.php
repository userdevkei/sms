@extends('layouts.app')
@section('title', 'Enter Marks')

@section('content')
    <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
            <h1 class="h4 mb-1">{{ $assessment->name }}</h1>
            <p class="text-muted mb-0">
                {{ $assessment->learningArea->name }} - {{ $assessment->stream->full_name }} -
                {{ $assessment->academicTerm->academic_year }} Term {{ $assessment->academicTerm->term_number }}
                @if($assessment->max_score) - Out of {{ $assessment->max_score }} @endif
            </p>
        </div>
        <a href="{{ route('results.assessments.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Back</a>
    </div>

    @if($assessment->status === 'locked')
        <div class="alert alert-warning"><i class="bi bi-lock me-1"></i> This assessment is locked. Marks are read-only.</div>
    @endif

    <form method="POST" action="{{ route('results.marks-entry.update', $assessment->id) }}">
        @method('PUT')
        @csrf
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                        <tr>
                            <th>Student</th><th>Admission No.</th>
                            @if($assessment->isCompetencyBased())
                                <th style="width:180px;">Rating</th>
                            @else
                                <th style="width:140px;">Score</th>
                            @endif
                            <th style="width:90px;" class="text-center">Absent</th>
                            <th>Remarks</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($enrollments as $index => $enrollment)
                            @php $existing = $enrollment->assessmentResults->first(); @endphp
                            <tr>
                                <td>{{ $enrollment->student->full_name }}</td>
                                <td>{{ $enrollment->student->userID ?: '-' }}</td>
                                <input type="hidden" name="results[{{ $index }}][enrollment_id]" value="{{ $enrollment->id }}">
                                <td>
                                    @if($assessment->isCompetencyBased())
                                        <select name="results[{{ $index }}][competency_level]" class="form-select form-select-sm" {{ $assessment->status === 'locked' ? 'disabled' : '' }}>
                                            <option value="">-</option>
                                            @foreach(['EE' => 'Exceeding Expectation', 'ME' => 'Meeting Expectation', 'AE' => 'Approaching Expectation', 'BE' => 'Below Expectation'] as $val => $label)
                                                <option value="{{ $val }}" @selected(old("results.$index.competency_level", $existing?->competency_level) === $val)>{{ $val }} - {{ $label }}</option>
                                            @endforeach
                                        </select>
                                    @else
                                        <input type="number" step="0.01" min="0" max="{{ $assessment->max_score }}"
                                               name="results[{{ $index }}][score]" class="form-control form-control-sm"
                                               value="{{ old("results.$index.score", $existing?->score) }}" {{ $assessment->status === 'locked' ? 'disabled' : '' }}>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <input type="checkbox" class="form-check-input absent-checkbox" name="results[{{ $index }}][is_absent]" value="1"
                                        @checked(old("results.$index.is_absent", $existing?->is_absent)) {{ $assessment->status === 'locked' ? 'disabled' : '' }}>
                                </td>
                                <td>
                                    <input type="text" name="results[{{ $index }}][remarks]" class="form-control form-control-sm"
                                           value="{{ old("results.$index.remarks", $existing?->remarks) }}" {{ $assessment->status === 'locked' ? 'disabled' : '' }}>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-3">No students in this class.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($enrollments->isNotEmpty() && $assessment->status !== 'locked')
                <div class="card-footer bg-white border-0 p-4 d-flex justify-content-between align-items-center">
                    @if($canFinalize)
                        <a href="{{ route('results.term-subject.preview', [$assessment->stream_id, $assessment->learning_area_id, $assessment->academic_term_id]) }}"
                           class="btn btn-sm btn-outline-success">
                            <i class="bi bi-check2-circle me-1"></i> Finalize Subject Results
                        </a>
                    @else
                        <span></span>
                    @endif
                    <button type="submit" class="btn btn-sm btn-primary px-4">Save Marks</button>
                </div>
            @endif
            {{--@if($enrollments->isNotEmpty() && $assessment->status !== 'locked')
                <div class="card-footer bg-white border-0 p-4 d-flex justify-content-end">
                    <button type="submit" class="btn btn-sm btn-primary px-4">Save Marks</button>
                </div>
            @endif--}}
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        // Disable the score/rating input for a row when marked absent, without
        // discarding whatever the teacher already typed (in case "absent" is
        // unchecked again before submitting).
        document.querySelectorAll('.absent-checkbox').forEach(cb => {
            cb.addEventListener('change', function () {
                const row = this.closest('tr');
                const input = row.querySelector('input[type="number"], select');
                if (input) input.disabled = this.checked;
            });
        });
    </script>
@endpush
