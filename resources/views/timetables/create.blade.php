@extends('layouts.app')
@section('title', 'New Timetable')
@section('content')
    <h1 class="h4 mb-3">New Timetable</h1>

    @error('slot_groups')
    <div class="alert alert-danger">{{ $message }}</div>
    @enderror

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('timetables.store') }}">
                @csrf
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label class="form-label">Academic Year</label>
                        <input type="text" name="academic_year" class="form-control" value="{{ old('academic_year', now()->year) }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Term</label>
                        <select name="term" class="form-select" required>
                            @foreach([1,2,3] as $t) <option value="{{ $t }}">Term {{ $t }}</option> @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Name (optional)</label>
                        <input type="text" name="name" class="form-control" placeholder="Leave blank to auto-generate">
                    </div>
                </div>

                @foreach($educationLevels as $level)
                    @php $levelGrades = $gradeLevels->where('education_level_id', $level->id); @endphp
                    @continue($levelGrades->isEmpty())
                    <div class="border rounded p-3 mb-3">
                        <div class="row g-3 align-items-end mb-2">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">{{ $level->name }}</label>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small text-muted">Time Slot Group</label>
                                <select name="slot_groups[{{ $level->id }}]" class="form-select form-select-sm">
                                    <option value="">— Select —</option>
                                    @foreach($slotGroups as $group)
                                        <option value="{{ $group->id }}"
                                            @selected(old("slot_groups.{$level->id}", $level->default_time_slot_group_id) === $group->id)>
                                            {{ $group->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="row g-2">
                            @foreach($levelGrades as $grade)
                                <div class="col-md-3">
                                    <div class="form-check">
                                        <input type="checkbox" name="grade_level_ids[]" value="{{ $grade->id }}" class="form-check-input" id="grade-{{ $grade->id }}">
                                        <label class="form-check-label" for="grade-{{ $grade->id }}">{{ $grade->name }}</label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach

                <button type="submit" class="btn btn-sm btn-primary">Create & Continue to Generate</button>
            </form>
        </div>
    </div>
@endsection
