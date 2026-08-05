@php $isEdit = isset($user); @endphp

<div class="row g-4">
    <div class="col-12 col-lg-4">
        <div class="card border-0 shadow-sm text-center">
            <div class="card-body">
                <div class="avatar-upload-wrapper mx-auto mb-3">
                    <img id="avatarPreview"
                         src="{{ $isEdit ? $user->avatar_url : route('file', ['path' => 'Files/images/avatar.png']) }}"
                         class="rounded-circle avatar-preview" alt="Avatar">
                    <label for="avatar" class="avatar-upload-btn">
                        <i class="bi bi-camera-fill"></i>
                    </label>
                    <input type="file" name="avatar" id="avatar" accept="image/*" class="d-none">
                </div>
                <p class="text-muted small mb-0">JPG or PNG. Max 2MB.</p>
                @error('avatar')<div class="text-danger small mt-1">{{ $message }}</div>@enderror

                <hr>

                <div class="text-start">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select @error('status') is-invalid @enderror">
                        @foreach(['pending' => 'Pending', 'active' => 'Active', 'suspended' => 'Suspended', 'transferred' => 'Transferred', 'graduated' => 'Graduated', 'deceased' => 'Deceased', 'terminated' => 'Terminated'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('status', $isEdit ? $user->status : 'pending') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('status')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>

                <div class="text-start mt-3">
                    <label class="form-label">Roles</label>
                    <div class="border rounded p-2" style="max-height: 350px; overflow-y: auto;">
                        @forelse($roles as $role)
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="roles[]"
                                       value="{{ $role->id }}" id="role-{{ $role->id }}"
                                    @checked(in_array($role->id, old('roles', $userRoleIds ?? [])))>
                                <label class="form-check-label small" for="role-{{ $role->id }}">{{ $role->name }}</label>
                            </div>
                        @empty
                            <p class="text-muted small mb-0">No roles available yet.</p>
                        @endforelse
                    </div>
                    @error('roles')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h6 class="text-uppercase text-muted small mb-3">Personal Details</h6>
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label class="form-label">First Name <span class="text-danger">*</span></label>
                        <input type="text" name="first_name" class="form-control @error('first_name') is-invalid @enderror"
                               value="{{ old('first_name', $isEdit ? $user->first_name : '') }}" required>
                        @error('first_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Middle Name</label>
                        <input type="text" name="middle_name" class="form-control @error('middle_name') is-invalid @enderror"
                               value="{{ old('middle_name', $isEdit ? $user->middle_name : '') }}">
                        @error('middle_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Last Name <span class="text-danger">*</span></label>
                        <input type="text" name="last_name" class="form-control @error('last_name') is-invalid @enderror"
                               value="{{ old('last_name', $isEdit ? $user->last_name : '') }}" required>
                        @error('last_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Gender</label>
                        <select name="gender" class="form-select @error('gender') is-invalid @enderror">
                            <option value="">Select</option>
                            <option value="male" @selected(old('gender', $isEdit ? $user->gender : '') === 'male')>Male</option>
                            <option value="female" @selected(old('gender', $isEdit ? $user->gender : '') === 'female')>Female</option>
                        </select>
                        @error('gender')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Date of Birth</label>
                        <input type="date" name="date_of_birth" class="form-control @error('date_of_birth') is-invalid @enderror"
                               value="{{ old('date_of_birth', $isEdit && $user->date_of_birth ? $user->date_of_birth->format('Y-m-d') : '') }}">
                        @error('date_of_birth')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">User ID</label>
                        <input type="text" name="userID" class="form-control @error('userID') is-invalid @enderror"
                               value="{{ old('userID', $isEdit ? $user->userID : '') }}" placeholder="Staff/Admission No.">
                        @error('userID')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Citizenship</label>
                        <input type="text" name="citizenship" class="form-control @error('citizenship') is-invalid @enderror"
                               value="{{ old('citizenship', $isEdit ? $user->citizenship : 'Kenyan') }}">
                        @error('citizenship')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
{{--                    <div class="col-md-4">--}}
{{--                        <label class="form-label">Ethnicity</label>--}}
{{--                        <input type="text" name="ethnicity" class="form-control @error('ethnicity') is-invalid @enderror"--}}
{{--                               value="{{ old('ethnicity', $isEdit ? $user->ethnicity : '') }}">--}}
{{--                        @error('ethnicity')<div class="invalid-feedback">{{ $message }}</div>@enderror--}}
{{--                    </div>--}}
                    <div class="col-md-4">
                        <label class="form-label">Ethnicity</label>
                        <select name="ethnicity" id="ethnicity" class="form-select select2-field @error('ethnicity') is-invalid @enderror">
                            <option value="">Select Ethnicity</option>
                            @foreach(config('ethnicities') as $ethnicity)
                                <option value="{{ $ethnicity }}" @selected(old('ethnicity', $isEdit ? $user->ethnicity : '') === $ethnicity)>
                                    {{ $ethnicity }}
                                </option>
                            @endforeach
                        </select>
                        @error('ethnicity')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                </div>

                <h6 class="text-uppercase text-muted small mb-3">Location</h6>
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label class="form-label">County</label>
                        <select name="county" id="county"
                                data-subcounties-url="{{ route('locations.subcounties') }}"
                                data-wards-url="{{ route('locations.wards') }}"
                                class="form-select select2-field @error('county') is-invalid @enderror">
                            <option value="">Select County</option>
                            @foreach(array_keys(config('counties')) as $countyName)
                                <option value="{{ $countyName }}" @selected(old('county', $isEdit ? $user->county : '') === $countyName)>
                                    {{ $countyName }}
                                </option>
                            @endforeach
                        </select>
                        @error('county')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Sub-County</label>
                        <select name="sub_county" id="subCounty"
                                class="form-select select2-field @error('sub_county') is-invalid @enderror" disabled>
                            <option value="">Select County First</option>
                        </select>
                        @error('sub_county')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Ward</label>
                        <select name="ward" id="ward"
                                class="form-select select2-field @error('ward') is-invalid @enderror" disabled>
                            <option value="">Select Sub-County First</option>
                        </select>
                        @error('ward')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                </div>

                <input type="hidden" id="currentSubCounty" value="{{ old('sub_county', $isEdit ? $user->sub_county : '') }}">
                <input type="hidden" id="currentWard" value="{{ old('ward', $isEdit ? $user->ward : '') }}">

                <h6 class="text-uppercase text-muted small mb-3">Account & Contact</h6>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                               value="{{ old('email', $isEdit ? $user->email : '') }}" required>
                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Phone Number</label>
                        <input type="text" name="phone_number" class="form-control @error('phone_number') is-invalid @enderror"
                               value="{{ old('phone_number', $isEdit ? $user->phone_number : '') }}" placeholder="07XXXXXXXX">
                        @error('phone_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Password <span class="text-danger">{{ $isEdit ? '' : '*' }}</span></label>
                        <input type="password" name="password" id="password"
                               class="form-control @error('password') is-invalid @enderror"
                               placeholder="{{ $isEdit ? 'Leave blank to keep current password' : 'Minimum 8 characters' }}"
                            {{ $isEdit ? '' : 'required' }}>
                        @error('password')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Confirm Password</label>
                        <input type="password" name="password_confirmation" class="form-control"
                               placeholder="{{ $isEdit ? 'Leave blank to keep current password' : 'Re-enter password' }}">
                    </div>
                </div>
            </div>

            <div class="card-footer bg-white border-0 p-4 pt-0 d-flex justify-content-end gap-2">
                <a href="{{ route('users.index') }}" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary px-4">{{ $isEdit ? 'Update User' : 'Create User' }}</button>
            </div>
        </div>
    </div>
</div>
