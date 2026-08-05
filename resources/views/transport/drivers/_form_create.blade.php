@php
    $preselectedUser = $preselectedUserId
        ? $eligibleUsers->firstWhere('id', $preselectedUserId)
        : null;
@endphp

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm text-center">
            <div class="card-body">
                @if($preselectedUser)
                    <img src="{{ route('file', ['path' => 'Files/images/avatar.png']) }}"
                         class="rounded-circle mb-3" width="90" height="90" style="object-fit:cover;" alt="{{ $preselectedUser->first_name }}">
                    <h6 class="mb-1">
                        {{ trim($preselectedUser->first_name . ' ' . ($preselectedUser->middle_name ? $preselectedUser->middle_name . ' ' : '') . $preselectedUser->last_name) }}
                    </h6>
                    <p class="text-muted small mb-3">{{ $preselectedUser->phone_number ?: 'No phone on file' }}</p>
                    <div class="alert alert-info small mb-0 text-start">
                        <i class="bi bi-info-circle me-1"></i>
                        Adding license details for this user. Wrong person?
                        <a href="{{ route('transport.drivers.create') }}">Choose a different user</a>.
                    </div>
                @else
                    <p class="text-muted small mb-3">
                        Only users already assigned the <strong>Driver</strong> role, and not yet linked to a driver record, appear below.
                        Assign the role first from <a href="{{ route('users.index') }}">Users</a> if the person you need isn't listed.
                    </p>
                    @if($eligibleUsers->isEmpty())
                        <div class="alert alert-warning small mb-0">No eligible users found.</div>
                    @endif
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                @if($preselectedUser)
                    <input type="hidden" name="user_id" value="{{ $preselectedUser->id }}">
                @else
                    <div class="mb-3">
                        <label class="form-label">User <span class="text-danger">*</span></label>
                        <select name="user_id" id="userSelect" class="form-select @error('user_id') is-invalid @enderror" required>
                            <option value="">Select a user</option>
                            @foreach($eligibleUsers as $user)
                                <option value="{{ $user->id }}" @selected(old('user_id') === $user->id)>
                                    {{ trim($user->first_name . ' ' . ($user->middle_name ? $user->middle_name . ' ' : '') . $user->last_name) }}
                                    @if($user->phone_number) — {{ $user->phone_number }} @endif
                                </option>
                            @endforeach
                        </select>
                        @error('user_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                @endif

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">License Number <span class="text-danger">*</span></label>
                        <input type="text" name="license_number" class="form-control @error('license_number') is-invalid @enderror"
                               value="{{ old('license_number') }}" required>
                        @error('license_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">License Class</label>
                        <input type="text" name="license_class" class="form-control @error('license_class') is-invalid @enderror"
                               value="{{ old('license_class') }}" placeholder="e.g. BCE">
                        @error('license_class')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">License Expiry</label>
                        <input type="date" name="license_expiry" class="form-control @error('license_expiry') is-invalid @enderror"
                               value="{{ old('license_expiry') }}">
                        @error('license_expiry')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select @error('status') is-invalid @enderror">
                            <option value="active" @selected(old('status', 'active') === 'active')>Active</option>
                            <option value="inactive" @selected(old('status') === 'inactive')>Inactive</option>
                        </select>
                        @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" rows="2" class="form-control @error('notes') is-invalid @enderror" placeholder="Optional">{{ old('notes') }}</textarea>
                        @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
            <div class="card-footer bg-white border-0 p-4 pt-0 d-flex justify-content-end gap-2">
                <a href="{{ route('transport.drivers.index') }}" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary px-4">Save License Details</button>
            </div>
        </div>
    </div>
</div>
