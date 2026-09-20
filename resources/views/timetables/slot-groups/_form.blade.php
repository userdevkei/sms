<h1 class="h4 mb-3">{{ $title }}</h1>
<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="POST" action="{{ $action }}">
            @csrf
            @if($method === 'PUT') @method('PUT') @endif
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label">Name</label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                           value="{{ old('name', $group->name ?? '') }}" placeholder="e.g. Secondary (40-min periods)" required>
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Code</label>
                    <input type="text" name="code" class="form-control @error('code') is-invalid @enderror"
                           value="{{ old('code', $group->code ?? '') }}" placeholder="e.g. SECONDARY" required>
                    @error('code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="2">{{ old('description', $group->description ?? '') }}</textarea>
            </div>
            <div class="mb-4">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="active" @selected(old('status', $group->status ?? 'active') === 'active')>Active</option>
                    <option value="inactive" @selected(old('status', $group->status ?? '') === 'inactive')>Inactive</option>
                </select>
            </div>
            <div class="d-flex justify-content-between">
                <a href="{{ route('timeslot-groups.index') }}" class="btn btn-sm btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-sm btn-primary">Save</button>
            </div>
        </form>
    </div>
</div>
