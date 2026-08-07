@php $isEdit = isset($stream); @endphp

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Grade Level <span class="text-danger">*</span></label>
                <select name="grade_level_id" id="gradeLevelSelect" class="form-select select2-field @error('grade_level_id') is-invalid @enderror" required>
                    <option value="">Select grade level</option>
                    @foreach($gradeLevels as $grade)
                        <option value="{{ $grade->id }}" data-senior="{{ $grade->educationLevel->code === 'SS' ? '1' : '0' }}"
                            @selected(old('grade_level_id', $isEdit ? $stream->grade_level_id : '') === $grade->id)>
                            {{ $grade->name }} ({{ $grade->educationLevel->name }})
                        </option>
                    @endforeach
                </select>
                @error('grade_level_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label class="form-label">Stream / Class Name <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                       value="{{ old('name', $isEdit ? $stream->name : '') }}" placeholder="e.g. East, A, Eagles" required>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6" id="pathwayField" style="display:none;">
                <label class="form-label">Pathway <span class="text-danger">*</span></label>
                <select name="pathway_id" class="form-select select2-field @error('pathway_id') is-invalid @enderror">
                    <option value="">Select pathway</option>
                    @foreach($pathways as $pathway)
                        <option value="{{ $pathway->id }}" @selected(old('pathway_id', $isEdit ? $stream->pathway_id : '') === $pathway->id)>{{ $pathway->name }}</option>
                    @endforeach
                </select>
                @error('pathway_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                <div class="form-text">Required for Senior Secondary classes only.</div>
            </div>

            <div class="col-md-3">
                <label class="form-label">Capacity</label>
                <input type="number" name="capacity" class="form-control @error('capacity') is-invalid @enderror"
                       value="{{ old('capacity', $isEdit ? $stream->capacity : '') }}" min="1">
                @error('capacity')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="active" @selected(old('status', $isEdit ? $stream->status : 'active') === 'active')>Active</option>
                    <option value="inactive" @selected(old('status', $isEdit ? $stream->status : '') === 'inactive')>Inactive</option>
                </select>
            </div>

            <div class="col-md-6">
                <label class="form-label">Class Teacher</label>
                <select name="class_teacher_id" class="form-select select2-field @error('class_teacher_id') is-invalid @enderror">
                    <option value="">Unassigned</option>
                    @foreach($classTeacherCandidates as $teacher)
                        <option value="{{ $teacher->id }}" @selected(old('class_teacher_id', $isEdit ? $stream->class_teacher_id : '') === $teacher->id)>
                            {{ trim($teacher->first_name . ' ' . ($teacher->middle_name ? $teacher->middle_name . ' ' : '') . $teacher->last_name) }}
                        </option>
                    @endforeach
                </select>
                @error('class_teacher_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>
        </div>
    </div>
    <div class="card-footer bg-white border-0 p-4 pt-0 d-flex justify-content-end gap-2">
        <a href="{{ route('curriculum.streams.index') }}" class="btn btn-sm btn-outline-secondary">Cancel</a>
        <button type="submit" class="btn btn-sm btn-primary px-4">{{ $isEdit ? 'Update Stream' : 'Create Stream' }}</button>
    </div>
</div>
