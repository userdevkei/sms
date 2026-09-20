@extends('layouts.app')
@section('title', 'Lesson Requirements — ' . $gradeLevel->name)
@section('content')
    <h1 class="h4 mb-1">Lesson Requirements — {{ $gradeLevel->name }}</h1>
    <p class="text-muted small mb-3">Tick a subject to include it in this grade's timetable, then set its weekly lesson count.</p>

    @error('requirements')
    <div class="alert alert-danger">{{ $message }}</div>
    @enderror

    <form method="POST" action="{{ route('curriculum.lesson-requirements.update', $gradeLevel->id) }}">
        @csrf
        @method('PUT')

        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                    <tr>
                        <th style="width:40px"></th>
                        <th>Subject</th>
                        <th style="width:160px">Lessons / Week</th>
                        <th style="width:160px">Double Lessons / Week</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($learningAreas as $i => $area)
                        @php $req = $existing->get($area->id); @endphp
                        <tr>
                            <td>
                                <input type="checkbox" class="form-check-input requirement-toggle"
                                       name="requirements[{{ $i }}][enabled]" value="1"
                                       data-row="{{ $i }}" @checked($req)>
                            </td>
                            <td>
                                {{ $area->name }}
                                @if($area->is_compulsory)
                                    <span class="badge bg-secondary-subtle text-secondary border ms-1">Compulsory</span>
                                @endif
                                <input type="hidden" name="requirements[{{ $i }}][learning_area_id]" value="{{ $area->id }}">
                            </td>
                            <td>
                                <input type="number" min="0" max="15" class="form-control form-control-sm"
                                       name="requirements[{{ $i }}][lessons_per_week]"
                                       value="{{ old("requirements.$i.lessons_per_week", $req->lessons_per_week ?? 5) }}"
                                       {{ $req ? '' : 'disabled' }} data-row-input="{{ $i }}">
                            </td>
                            <td>
                                <input type="number" min="0" max="15" class="form-control form-control-sm"
                                       name="requirements[{{ $i }}][double_lessons_per_week]"
                                       value="{{ old("requirements.$i.double_lessons_per_week", $req->double_lessons_per_week ?? 0) }}"
                                       {{ $req ? '' : 'disabled' }} data-row-input="{{ $i }}">
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="card-footer bg-white border-0 d-flex justify-content-between">
                <a href="{{ route('curriculum.lesson-requirements.index') }}" class="btn btn-sm btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-sm btn-primary">Save</button>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Disabled inputs are never submitted (same rule that bit the timetable bulk-add form) —
            // so a subject left unchecked must have its number inputs disabled too, not just greyed out,
            // or an unchecked-but-still-enabled row would submit stale numbers with no 'enabled' flag.
            document.querySelectorAll('.requirement-toggle').forEach(function (toggle) {
                toggle.addEventListener('change', function () {
                    const row = this.dataset.row;
                    document.querySelectorAll(`[data-row-input="${row}"]`).forEach(input => {
                        input.disabled = !toggle.checked;
                    });
                });
            });
        });
    </script>
@endpush
