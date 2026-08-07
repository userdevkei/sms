@php $isEdit = isset($vehicle); @endphp

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Registration Number <span class="text-danger">*</span></label>
                <input type="text" name="registration_number" class="form-control text-uppercase @error('registration_number') is-invalid @enderror"
                       value="{{ old('registration_number', $isEdit ? $vehicle->registration_number : '') }}" placeholder="KDA 123X" required>
                @error('registration_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label class="form-label">Make</label>
                <input type="text" name="make" class="form-control" value="{{ old('make', $isEdit ? $vehicle->make : '') }}" placeholder="e.g. Isuzu">
            </div>
            <div class="col-md-4">
                <label class="form-label">Model</label>
                <input type="text" name="model" class="form-control" value="{{ old('model', $isEdit ? $vehicle->model : '') }}" placeholder="e.g. NQR">
            </div>

            <div class="col-md-3">
                <label class="form-label">Year of Manufacture</label>
                <input type="number" name="year_of_manufacture" class="form-control @error('year_of_manufacture') is-invalid @enderror"
                       value="{{ old('year_of_manufacture', $isEdit ? $vehicle->year_of_manufacture : '') }}" min="1980" max="{{ date('Y') + 1 }}">
                @error('year_of_manufacture')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-3">
                <label class="form-label">Seating Capacity <span class="text-danger">*</span></label>
                <input type="number" name="capacity" class="form-control @error('capacity') is-invalid @enderror"
                       value="{{ old('capacity', $isEdit ? $vehicle->capacity : '') }}" min="1" required>
                @error('capacity')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-3">
                <label class="form-label">Color</label>
                <input type="text" name="color" class="form-control" value="{{ old('color', $isEdit ? $vehicle->color : '') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    @foreach(['active' => 'Active', 'under_maintenance' => 'Under Maintenance', 'inactive' => 'Inactive'] as $val => $label)
                        <option value="{{ $val }}" @selected(old('status', $isEdit ? $vehicle->status : 'active') === $val)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label">Logbook Number</label>
                <input type="text" name="logbook_number" class="form-control" value="{{ old('logbook_number', $isEdit ? $vehicle->logbook_number : '') }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">Insurance Expiry</label>
                <input type="date" name="insurance_expiry" class="form-control"
                       value="{{ old('insurance_expiry', $isEdit && $vehicle->insurance_expiry ? $vehicle->insurance_expiry->format('Y-m-d') : '') }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">Inspection Expiry (NTSA)</label>
                <input type="date" name="inspection_expiry" class="form-control"
                       value="{{ old('inspection_expiry', $isEdit && $vehicle->inspection_expiry ? $vehicle->inspection_expiry->format('Y-m-d') : '') }}">
            </div>

            <div class="col-12">
                <label class="form-label">Notes</label>
                <textarea name="notes" rows="2" class="form-control">{{ old('notes', $isEdit ? $vehicle->notes : '') }}</textarea>
            </div>
        </div>
    </div>
    <div class="card-footer bg-white border-0 p-4 pt-0 d-flex justify-content-end gap-2">
        <a href="{{ route('transport.vehicles.index') }}" class="btn btn-outline-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary px-4">{{ $isEdit ? 'Update Vehicle' : 'Add Vehicle' }}</button>
    </div>
</div>
