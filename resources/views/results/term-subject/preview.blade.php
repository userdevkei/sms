@extends('layouts.app')
@section('title', 'Finalize Subject Results')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <div class="">
            <h1 class="h4 mb-1">Finalize: {{ $learningArea->name }} - {{ $stream->full_name }}</h1>
            <p class="text-muted mb-0">{{ $academicTerm->academic_year }} Term {{ $academicTerm->term_number }}. Averages every open/locked assessment entered so far.</p>
        </div>
        <a href="{{ route('results.assessments.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Back</a>
    </div>

    <form method="POST" action="{{ route('results.term-subject.finalize', [$stream->id, $learningArea->id, $academicTerm->id]) }}">
        @csrf
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead><tr><th>Student</th><th>Assessments Counted</th><th>{{ $isCompetency ? 'Rating' : 'Average' }}</th><th>Letter Grade</th><th>Remarks</th></tr></thead>
                        <tbody>
                        @foreach($rows as $row)
                            @php $existingRow = $existing[$row['enrollment']->id] ?? null; @endphp
                            <tr>
                                <td>{{ $row['enrollment']->student->full_name }}</td>
                                <td>{{ $row['count'] }}</td>
                                <td>
                                    @if($isCompetency)
                                        {{ $row['competency'] ?? '-' }}
                                    @else
                                        {{ $row['average'] !== null ? number_format($row['average'], 1) : '-' }}
                                    @endif
                                </td>
                                <td>{{ !$isCompetency && $row['average'] !== null ? (\App\Models\GradingBand::letterFor($row['average']) ?? '-') : '-' }}</td>
                                <td><input type="text" name="remarks[{{ $row['enrollment']->id }}]" class="form-control form-control-sm" value="{{ $existingRow?->teacher_remarks }}"></td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-white border-0 p-4 d-flex justify-content-end">
                <button type="submit" class="btn btn-sm btn-primary px-4" onclick="return confirm('Finalize subject results for this class? This can be re-run later if more assessments are added.')">
                    Finalize Subject Results
                </button>
            </div>
        </div>
    </form>
@endsection
