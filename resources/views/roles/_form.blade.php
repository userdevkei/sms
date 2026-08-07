@php $isEdit = isset($role); $isSuperAdmin = $isEdit && $role->slug === 'super_admin'; @endphp

<div class="row g-4">
    <div class="col-12 col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h6 class="text-uppercase text-muted small mb-3">Role Details</h6>

                <div class="mb-3">
                    <label class="form-label">Role Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" id="roleName"
                           class="form-control @error('name') is-invalid @enderror"
                           value="{{ old('name', $isEdit ? $role->name : '') }}"
                           placeholder="e.g. Exams Coordinator" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                @if($isEdit)
                    <div class="mb-3">
                        <label class="form-label">Slug</label>
                        <input type="text" class="form-control" value="{{ $role->slug }}" disabled>
                        <div class="form-text">Slugs can't change after a role is created — the system relies on them internally.</div>
                    </div>
                @else
                    <div class="mb-3">
                        <label class="form-label">Slug Preview</label>
                        <input type="text" class="form-control" id="slugPreview" value="" disabled placeholder="auto-generated">
                        <div class="form-text">Generated automatically from the role name.</div>
                    </div>
                @endif

                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea name="description" rows="3"
                              class="form-control @error('description') is-invalid @enderror"
                              placeholder="What is this role for?">{{ old('description', $isEdit ? $role->description : '') }}</textarea>
                    @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                @if($isSuperAdmin)
                    <div class="alert alert-warning small mb-0">
                        <i class="bi bi-shield-lock me-1"></i>
                        Super Admin always has every permission. The list on the right is shown for reference and can't be changed.
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="text-uppercase text-muted small mb-0">Permissions</h6>
                    @unless($isSuperAdmin)
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="selectAllPermissions">
                            <label class="form-check-label small" for="selectAllPermissions">Select All</label>
                        </div>
                    @endunless
                </div>

                <div class="accordion" id="permissionsAccordion">
                    @foreach($permissionsByModule as $module => $permissions)
                        @php $moduleKey = \Illuminate\Support\Str::slug($module); @endphp
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button"
                                        data-bs-toggle="collapse" data-bs-target="#module-{{ $moduleKey }}">
                                    <span class="text-capitalize">{{ str_replace('_', ' ', $module) }}</span>
                                    <span class="badge bg-secondary-subtle text-secondary ms-2 module-selected-count" data-module="{{ $moduleKey }}">
                                        0/{{ $permissions->count() }}
                                    </span>
                                </button>
                            </h2>
                            <div id="module-{{ $moduleKey }}" class="accordion-collapse collapse" data-bs-parent="#permissionsAccordion">
                                <div class="accordion-body">
                                    <div class="mb-2">
                                        <div class="form-check">
                                            <input class="form-check-input module-select-all" type="checkbox"
                                                   id="moduleAll-{{ $moduleKey }}" data-module="{{ $moduleKey }}"
                                                {{ $isSuperAdmin ? 'disabled' : '' }}>
                                            <label class="form-check-label small fw-semibold" for="moduleAll-{{ $moduleKey }}">
                                                Select all in {{ str_replace('_', ' ', $module) }}
                                            </label>
                                        </div>
                                    </div>
                                    <div class="row">
                                        @foreach($permissions as $permission)
                                            <div class="col-md-6 mb-2">
                                                <div class="form-check">
                                                    <input class="form-check-input permission-checkbox" type="checkbox"
                                                           name="permissions[]" value="{{ $permission->id }}"
                                                           id="perm-{{ $permission->id }}"
                                                           data-module="{{ $moduleKey }}"
                                                        {{ $isSuperAdmin || in_array($permission->id, old('permissions', $rolePermissionIds ?? [])) ? 'checked' : '' }}
                                                        {{ $isSuperAdmin ? 'disabled' : '' }}>
                                                    <label class="form-check-label small" for="perm-{{ $permission->id }}">
                                                        {{ $permission->name }}
                                                        @if($permission->description)
                                                            <i class="bi bi-info-circle text-muted ms-1" title="{{ $permission->description }}"></i>
                                                        @endif
                                                    </label>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                @error('permissions')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
            </div>

            <div class="card-footer bg-white border-0 p-4 pt-0 d-flex justify-content-end gap-2">
                <a href="{{ route('roles.index') }}" class="btn btn-sm btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-sm btn-primary px-4">{{ $isEdit ? 'Update Role' : 'Create Role' }}</button>
            </div>
        </div>
    </div>
</div>
