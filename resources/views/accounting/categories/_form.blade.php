{{-- Included by income-categories/create.blade.php, income-categories/edit.blade.php,
     expense-categories/create.blade.php, expense-categories/edit.blade.php.
     Expects: $type ('income'|'expense'), $title, $action, $method ('POST'|'PUT'), $category (null on create) --}}
<h1 class="h4 mb-3">{{ $title }}</h1>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="POST" action="{{ $action }}">
            @csrf
            @if($method === 'PUT') @method('PUT') @endif

            <div class="mb-3">
                <label class="form-label">Name</label>
                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                       value="{{ old('name', $category->name ?? '') }}" required>
                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="2">{{ old('description', $category->description ?? '') }}</textarea>
                @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="mb-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select @error('status') is-invalid @enderror">
                    <option value="active" @selected(old('status', $category->status ?? 'active') === 'active')>Active</option>
                    <option value="inactive" @selected(old('status', $category->status ?? '') === 'inactive')>Inactive</option>
                </select>
                @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="d-flex justify-content-between">
                <a href="{{ route("accounting.{$type}-categories.index") }}" class="btn btn-sm btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-sm btn-primary">Save</button>
            </div>
        </form>
    </div>
</div>
