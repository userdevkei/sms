@php
    $isEdit = isset($learningArea);
    $selectedIds = old('grade_levels', $selectedGradeLevelIds ?? []);
@endphp

<div class="row g-4">
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                           value="{{ old('name', $isEdit ? $learningArea->name : '') }}" placeholder="e.g. Mathematics" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">Code</label>
                    <input type="text" name="code" class="form-control" value="{{ old('code', $isEdit ? $learningArea->code : '') }}" placeholder="e.g. MAT">
                </div>
                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea name="description" rows="3" class="form-control">{{ old('description', $isEdit ? $learningArea->description : '') }}</textarea>
                </div>
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" name="is_compulsory" id="is_compulsory" value="1"
                        @checked(old('is_compulsory', $isEdit ? $learningArea->is_compulsory : true))>
                    <label class="form-check-label" for="is_compulsory">Compulsory subject</label>
                </div>
                <div class="mb-0">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="active" @selected(old('status', $isEdit ? $learningArea->status : 'active') === 'active')>Active</option>
                        <option value="inactive" @selected(old('status', $isEdit ? $learningArea->status : '') === 'inactive')>Inactive</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h6 class="text-uppercase text-muted small mb-3">Offered At These Grade Levels</h6>
                <div class="accordion" id="gradeLevelAccordion">
                    @foreach($educationLevels as $level)
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#level-{{ $level->id }}">
                                    {{ $level->name }}
                                </button>
                            </h2>
                            <div id="level-{{ $level->id }}" class="accordion-collapse collapse" data-bs-parent="#gradeLevelAccordion">
                                <div class="accordion-body">
                                    @forelse($level->gradeLevels as $grade)
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="grade_levels[]" value="{{ $grade->id }}"
                                                   id="grade-{{ $grade->id }}" @checked(in_array($grade->id, $selectedIds))>
                                            <label class="form-check-label small" for="grade-{{ $grade->id }}">{{ $grade->name }}</label>
                                        </div>
                                    @empty
                                        <p class="text-muted small mb-0">No grade levels under this education level yet.</p>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="card-footer bg-white border-0 p-4 pt-0 d-flex justify-content-end gap-2">
                <a href="{{ route('curriculum.learning-areas.index') }}" class="btn btn-sm btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-sm btn-primary px-4">{{ $isEdit ? 'Update Learning Area' : 'Create Learning Area' }}</button>
            </div>
        </div>
    </div>
</div>
