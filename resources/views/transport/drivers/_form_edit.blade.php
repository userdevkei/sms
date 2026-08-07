<div class="row g-4">
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm text-center">
            <div class="card-body">
                <img src="{{ $driver->avatar_url }}" class="rounded-circle mb-3" width="90" height="90" style="object-fit:cover;" alt="{{ $driver->full_name }}">
                <h6 class="mb-1">{{ $driver->full_name }}</h6>
                <p class="text-muted small mb-0">{{ $driver->email }}</p>
                <p class="text-muted small">{{ $driver->phone_number ?: '—' }}</p>
                <a href="{{ route('users.edit', $driver->user_id) }}" class="btn btn-sm btn-outline-secondary w-100 mt-2">
                    <i class="bi bi-person-gear me-1"></i> Edit User Details
                </a>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">License Number <span class="text-danger">*</span></label>
                        <input type="text" name="license_number" class="form-control @error('license_number') is-invalid @enderror"
                               value="{{ old('license_number', $driver->license_number) }}" required>
                        @error('license_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">License Class</label>
                        <input type="text" name="license_class" class="form-control" value="{{ old('license_class', $driver->license_class) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">License Expiry</label>
                        <input type="date" name="license_expiry" class="form-control"
                               value="{{ old('license_expiry', $driver->license_expiry?->format('Y-m-d')) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="active" @selected(old('status', $driver->status) === 'active')>Active</option>
                            <option value="inactive" @selected(old('status', $driver->status) === 'inactive')>Inactive</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" rows="2" class="form-control">{{ old('notes', $driver->notes) }}</textarea>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-white border-0 p-4 pt-0 d-flex justify-content-end gap-2">
                <a href="{{ route('transport.drivers.index') }}" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary px-4">Update Driver</button>
            </div>
        </div>
    </div>
</div>
