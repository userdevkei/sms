@php $isEdit = isset($hostel); @endphp

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Hostel Name <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                       value="{{ old('name', $isEdit ? $hostel->name : '') }}" placeholder="e.g. Acacia Dorm" required>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-3">
                <label class="form-label">Gender <span class="text-danger">*</span></label>
                <select name="gender" class="form-select">
                    @foreach(['male' => 'Male', 'female' => 'Female', 'mixed' => 'Mixed'] as $val => $label)
                        <option value="{{ $val }}" @selected(old('gender', $isEdit ? $hostel->gender : 'mixed') === $val)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="active" @selected(old('status', $isEdit ? $hostel->status : 'active') === 'active')>Active</option>
                    <option value="inactive" @selected(old('status', $isEdit ? $hostel->status : '') === 'inactive')>Inactive</option>
                </select>
            </div>

            <div class="col-md-6">
                <label class="form-label">Warden</label>
                <select name="warden_id" class="form-select select2-field @error('warden_id') is-invalid @enderror">
                    <option value="">Unassigned</option>
                    @foreach($wardenCandidates as $candidate)
                        <option value="{{ $candidate->id }}" @selected(old('warden_id', $isEdit ? $hostel->warden_id : '') === $candidate->id)>
                            {{ trim($candidate->first_name . ' ' . ($candidate->middle_name ? $candidate->middle_name . ' ' : '') . $candidate->last_name) }}
                        </option>
                    @endforeach
                </select>
                @error('warden_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label class="form-label">Default Fee Per Term (KES)</label>
                <input type="number" step="0.01" name="default_fee_per_term" class="form-control @error('default_fee_per_term') is-invalid @enderror"
                       value="{{ old('default_fee_per_term', $isEdit ? $hostel->default_fee_per_term : '') }}" min="0">
                @error('default_fee_per_term')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <div class="form-text">Used unless a specific room sets its own fee.</div>
            </div>

            <div class="col-12">
                <label class="form-label">Description</label>
                <textarea name="description" rows="2" class="form-control">{{ old('description', $isEdit ? $hostel->description : '') }}</textarea>
            </div>
        </div>
    </div>
    <div class="card-footer bg-white border-0 p-4 pt-0 d-flex justify-content-end gap-2">
        <a href="{{ route('accommodation.hostels.index') }}" class="btn btn-sm btn-outline-secondary">Cancel</a>
        <button type="submit" class="btn btn-sm btn-primary px-4">{{ $isEdit ? 'Update Hostel' : 'Create Hostel' }}</button>
    </div>
</div>
