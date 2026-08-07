@php $isEdit = isset($route); @endphp

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Route Name <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                       value="{{ old('name', $isEdit ? $route->name : '') }}" placeholder="e.g. Kitengela – Isinya Route" required>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-3">
                <label class="form-label">Route Code</label>
                <input type="text" name="code" class="form-control @error('code') is-invalid @enderror"
                       value="{{ old('code', $isEdit ? $route->code : '') }}" placeholder="e.g. RT-01">
                @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="active" @selected(old('status', $isEdit ? $route->status : 'active') === 'active')>Active</option>
                    <option value="inactive" @selected(old('status', $isEdit ? $route->status : '') === 'inactive')>Inactive</option>
                </select>
            </div>
            <div class="col-12">
                <label class="form-label">Description</label>
                <textarea name="description" rows="2" class="form-control">{{ old('description', $isEdit ? $route->description : '') }}</textarea>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-1">
            <h6 class="text-uppercase text-muted small mb-0">Pick-up / Drop-off Points & Fare</h6>
            <button type="button" id="addStopBtn" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-plus-lg me-1"></i> Add Stop
            </button>
        </div>
        <p class="text-muted small mb-3">
            <i class="bi bi-info-circle"></i> Stops are billed per term, in the order listed below.
        </p>

        @error('stops')<div class="alert alert-danger small py-2">{{ $message }}</div>@enderror

        <div id="stopsContainer">
            @php $existingStops = old('stops', $isEdit ? $route->stops->map(fn($s) => ['id' => $s->id, 'name' => $s->name, 'landmark_description' => $s->landmark_description, 'fare' => $s->fare])->all() : []); @endphp
            @forelse($existingStops as $i => $stop)
                <div class="stop-row" data-index="{{ $i }}">
                    <div class="stop-row-fields">
                        <input type="hidden" name="stops[{{ $i }}][id]" value="{{ $stop['id'] ?? '' }}">
                        <div class="row g-2">
                            <div class="col-12 col-md-1">
                                <div class="stop-row-number">{{ $i + 1 }}</div>
                            </div>
                            <div class="col-12 col-md-4">
                                <input type="text" name="stops[{{ $i }}][name]" class="form-control form-control-sm"
                                       placeholder="Stop name" value="{{ $stop['name'] ?? '' }}" required>
                            </div>
                            <div class="col-12 col-md-4">
                                <input type="text" name="stops[{{ $i }}][landmark_description]" class="form-control form-control-sm"
                                       placeholder="Landmark (optional)" value="{{ $stop['landmark_description'] ?? '' }}">
                            </div>
                            <div class="col-8 col-md-2">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">KES</span>
                                    <input type="number" step="0.01" min="0" name="stops[{{ $i }}][fare]" class="form-control"
                                           placeholder="Fare" value="{{ $stop['fare'] ?? '' }}" required>
                                </div>
                            </div>
                            <div class="col-4 col-md-1 d-flex align-items-center justify-content-end">
                                <button type="button" class="btn btn-sm btn-outline-danger remove-stop" title="Remove stop">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <p class="text-muted small fst-italic mb-0" id="noStopsMessage">No stops added yet. Click "Add Stop" to begin.</p>
            @endforelse
        </div>

        <template id="stopRowTemplate">
            <div class="stop-row" data-index="__INDEX__">
                <div class="stop-row-fields">
                    <input type="hidden" name="stops[__INDEX__][id]" value="">
                    <div class="row g-2">
                        <div class="col-12 col-md-1">
                            <div class="stop-row-number"><span class="stop-number"></span></div>
                        </div>
                        <div class="col-12 col-md-4">
                            <input type="text" name="stops[__INDEX__][name]" class="form-control form-control-sm" placeholder="Stop name" required>
                        </div>
                        <div class="col-12 col-md-4">
                            <input type="text" name="stops[__INDEX__][landmark_description]" class="form-control form-control-sm" placeholder="Landmark (optional)">
                        </div>
                        <div class="col-8 col-md-2">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text">KES</span>
                                <input type="number" step="0.01" min="0" name="stops[__INDEX__][fare]" class="form-control" placeholder="Fare" required>
                            </div>
                        </div>
                        <div class="col-4 col-md-1 d-flex align-items-center justify-content-end">
                            <button type="button" class="btn btn-sm btn-outline-danger remove-stop" title="Remove stop">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>
    <div class="card-footer bg-white border-0 p-4 pt-0 d-flex justify-content-end gap-2">
        <a href="{{ route('transport.transport-routes.index') }}" class="btn btn-sm btn-outline-secondary">Cancel</a>
        <button type="submit" class="btn btn-sm btn-primary px-4">{{ $isEdit ? 'Update Route' : 'Create Route' }}</button>
    </div>
</div>
