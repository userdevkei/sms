@php $isEdit = isset($gradeLevel); @endphp

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Education Level <span class="text-danger">*</span></label>
                <select name="education_level_id" class="form-select select2-field @error('education_level_id') is-invalid @enderror" required>
                    <option value="">Select level</option>
                    @foreach($educationLevels as $level)
                        <option value="{{ $level->id }}" @selected(old('education_level_id', $isEdit ? $gradeLevel->education_level_id : '') === $level->id)>{{ $level->name }}</option>
                    @endforeach
                </select>
                @error('education_level_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label class="form-label">Grade Name <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                       value="{{ old('name', $isEdit ? $gradeLevel->name : '') }}" placeholder="e.g. Grade 7" required>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label class="form-label">Code</label>
                <input type="text" name="code" class="form-control" value="{{ old('code', $isEdit ? $gradeLevel->code : '') }}" placeholder="e.g. G7">
            </div>
            <div class="col-md-3">
                <label class="form-label">Sequence <span class="text-danger">*</span></label>
                <input type="number" name="sequence" class="form-control @error('sequence') is-invalid @enderror"
                       value="{{ old('sequence', $isEdit ? $gradeLevel->sequence : ($suggestedSequence ?? '')) }}" min="1" required>
                @error('sequence')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <div class="form-text">Global order across all levels — determines progression.</div>
            </div>
            <div class="col-md-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="active" @selected(old('status', $isEdit ? $gradeLevel->status : 'active') === 'active')>Active</option>
                    <option value="inactive" @selected(old('status', $isEdit ? $gradeLevel->status : '') === 'inactive')>Inactive</option>
                </select>
            </div>
        </div>
    </div>
    <div class="card-footer bg-white border-0 p-4 pt-0 d-flex justify-content-end gap-2">
        <a href="{{ route('curriculum.grade-levels.index') }}" class="btn btn-sm btn-outline-secondary">Cancel</a>
        <button type="submit" class="btn btn-sm btn-primary px-4">{{ $isEdit ? 'Update Grade Level' : 'Create Grade Level' }}</button>
    </div>
</div>
