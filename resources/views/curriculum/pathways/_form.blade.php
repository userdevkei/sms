@php
    $isEdit = isset($pathway);
    $selectedIds = old('learning_areas', $selectedLearningAreaIds ?? []);
@endphp

<div class="row g-4">
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Pathway Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                           value="{{ old('name', $isEdit ? $pathway->name : '') }}" placeholder="e.g. STEM" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">Code</label>
                    <input type="text" name="code" class="form-control" value="{{ old('code', $isEdit ? $pathway->code : '') }}" placeholder="e.g. STEM">
                </div>
                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea name="description" rows="3" class="form-control">{{ old('description', $isEdit ? $pathway->description : '') }}</textarea>
                </div>
                <div class="mb-0">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="active" @selected(old('status', $isEdit ? $pathway->status : 'active') === 'active')>Active</option>
                        <option value="inactive" @selected(old('status', $isEdit ? $pathway->status : '') === 'inactive')>Inactive</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h6 class="text-uppercase text-muted small mb-3">Subjects Under This Pathway</h6>
                <div class="row">
                    @forelse($learningAreas as $area)
                        <div class="col-md-6 mb-2">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="learning_areas[]" value="{{ $area->id }}"
                                       id="la-{{ $area->id }}" @checked(in_array($area->id, $selectedIds))>
                                <label class="form-check-label small" for="la-{{ $area->id }}">{{ $area->name }}</label>
                            </div>
                        </div>
                    @empty
                        <p class="text-muted small mb-0">No learning areas defined yet — add subjects first.</p>
                    @endforelse
                </div>
            </div>
            <div class="card-footer bg-white border-0 p-4 pt-0 d-flex justify-content-end gap-2">
                <a href="{{ route('curriculum.pathways.index') }}" class="btn btn-sm btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-sm btn-primary px-4">{{ $isEdit ? 'Update Pathway' : 'Create Pathway' }}</button>
            </div>
        </div>
    </div>
</div>
