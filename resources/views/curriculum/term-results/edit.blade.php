@extends('layouts.app')
@section('title', 'Mark Term Results Complete')

@section('content')
    <div class="mb-3">
        <h1 class="h4 mb-1">Mark Term Results Complete - {{ $gradeLevel->name }}</h1>
        <p class="text-muted mb-0">
            <i class="bi bi-info-circle"></i> Manual override screen. Normally, this is set automatically when
            report cards are published in the Results module - use this only for edge cases (e.g. a transferred-in student).
        </p>
    </div>

    <form method="POST" action="{{ route('curriculum.term-results.update', $gradeLevel->id) }}">
        @csrf
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead><tr><th>Student</th><th>Academic Year</th><th class="text-center">Term 1</th><th class="text-center">Term 2</th><th class="text-center">Term 3</th></tr></thead>
                        <tbody>
                        @foreach($enrollments as $index => $enrollment)
                            @php
                                $done = ($completions[$enrollment->user_id] ?? collect())
                                    ->filter(fn($c) => $c->completed_at)
                                    ->pluck('term_number')
                                    ->all();
                            @endphp
                            <tr>
                                <td>{{ $enrollment->student->full_name }}</td>
                                <td>
                                    {{ $enrollment->academic_year }}
                                    <input type="hidden" name="completions[{{ $index }}][user_id]" value="{{ $enrollment->user_id }}">
                                    <input type="hidden" name="completions[{{ $index }}][academic_year]" value="{{ $enrollment->academic_year }}">
                                </td>
                                @foreach([1, 2, 3] as $term)
                                    <td class="text-center">
                                        <input type="checkbox" class="form-check-input" name="completions[{{ $index }}][terms][]" value="{{ $term }}"
                                            {{ in_array($term, $done) ? 'checked' : '' }}>
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-white border-0 p-4 d-flex justify-content-end">
                <button type="submit" class="btn btn-primary px-4">Save</button>
            </div>
        </div>
    </form>
@endsection
