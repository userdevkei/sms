{{-- Included by create.blade.php and edit.blade.php.
     Expects: $title, $action, $method ('POST'|'PUT'), $slot (null on create) --}}
<h1 class="h4 mb-3">{{ $title }}</h1>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="POST" action="{{ $action }}">
            @csrf
            @if($method === 'PUT') @method('PUT') @endif

            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <label class="form-label">Day</label>
                    <select name="day_of_week" class="form-select @error('day_of_week') is-invalid @enderror" required>
                        @foreach(\App\Models\TimeSlot::DAYS as $num => $name)
                            <option value="{{ $num }}" @selected(old('day_of_week', $slot->day_of_week ?? '') == $num)>{{ $name }}</option>
                        @endforeach
                    </select>
                    @error('day_of_week') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    <div class="form-text">Weekend days are valid — use them for remedial-only slots.</div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Start Time</label>
                    <input type="time" name="start_time" class="form-control @error('start_time') is-invalid @enderror"
                           value="{{ old('start_time', isset($slot) ? \Illuminate\Support\Carbon::parse($slot->start_time)->format('H:i') : '') }}" required>
                    @error('start_time') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">End Time</label>
                    <input type="time" name="end_time" class="form-control @error('end_time') is-invalid @enderror"
                           value="{{ old('end_time', isset($slot) ? \Illuminate\Support\Carbon::parse($slot->end_time)->format('H:i') : '') }}" required>
                    @error('end_time') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <label class="form-label">Type</label>
                    <select name="type" class="form-select @error('type') is-invalid @enderror" required>
                        <option value="main" @selected(old('type', $slot->type ?? 'main') === 'main')>Main Timetable</option>
                        <option value="remedial" @selected(old('type', $slot->type ?? '') === 'remedial')>Remedial</option>
                    </select>
                    @error('type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    <div class="form-text">Remedial slots sit outside official hours or on weekends and are never used by the generator.</div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Sequence</label>
                    <input type="number" name="sequence" min="1" class="form-control @error('sequence') is-invalid @enderror"
                           value="{{ old('sequence', $slot->sequence ?? '') }}" required>
                    @error('sequence') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    <div class="form-text">Row position within the day. Two slots with consecutive sequence numbers are treated as back-to-back for double lessons.</div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Label (optional)</label>
                    <input type="text" name="label" class="form-control" placeholder="e.g. Period 1, Morning Remedial"
                           value="{{ old('label', $slot->label ?? '') }}">
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="form-check mt-4">
                        <input type="checkbox" name="is_break" value="1" class="form-check-input" id="isBreak"
                            @checked(old('is_break', $slot->is_break ?? false))>
                        <label class="form-check-label" for="isBreak">This is a break (tea, lunch, assembly)</label>
                    </div>
                    <div class="form-text">Breaks reserve a row on the grid but are never scheduled and never count as adjacent for doubles.</div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="active" @selected(old('status', $slot->status ?? 'active') === 'active')>Active</option>
                        <option value="inactive" @selected(old('status', $slot->status ?? '') === 'inactive')>Inactive</option>
                    </select>
                </div>
            </div>

            <div class="d-flex justify-content-between">
                <a href="{{ route('timeslots.index') }}" class="btn btn-sm btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-sm btn-primary">Save</button>
            </div>
        </form>
    </div>
</div>
