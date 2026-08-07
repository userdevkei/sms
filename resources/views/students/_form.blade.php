@php $isEdit = isset($student); @endphp

<div class="row g-4">
    <div class="col-12 col-lg-4">
        <div class="card border-0 shadow-sm text-center">
            <div class="card-body">
                <div class="avatar-upload-wrapper mx-auto mb-3">
                    <img id="avatarPreview"
                         src="{{ $isEdit ? $student->avatar_url : route('file', ['path' => 'Files/images/avatar.png']) }}"
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
                            <option value="{{ $value }}" @selected(old('status', $isEdit ? $student->status : 'pending') === $value)>{{ $label }}</option>
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
                                    checked>
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
                               value="{{ old('first_name', $isEdit ? $student->first_name : '') }}" required>
                        @error('first_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Middle Name</label>
                        <input type="text" name="middle_name" class="form-control @error('middle_name') is-invalid @enderror"
                               value="{{ old('middle_name', $isEdit ? $student->middle_name : '') }}">
                        @error('middle_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Last Name <span class="text-danger">*</span></label>
                        <input type="text" name="last_name" class="form-control @error('last_name') is-invalid @enderror"
                               value="{{ old('last_name', $isEdit ? $student->last_name : '') }}" required>
                        @error('last_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Gender</label>
                        <select name="gender" class="form-select @error('gender') is-invalid @enderror">
                            <option value="">Select</option>
                            <option value="male" @selected(old('gender', $isEdit ? $student->gender : '') === 'male')>Male</option>
                            <option value="female" @selected(old('gender', $isEdit ? $student->gender : '') === 'female')>Female</option>
                        </select>
                        @error('gender')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Date of Birth</label>
                        <input type="date" name="date_of_birth" class="form-control @error('date_of_birth') is-invalid @enderror"
                               value="{{ old('date_of_birth', $isEdit && $student->date_of_birth ? $student->date_of_birth->format('Y-m-d') : '') }}">
                        @error('date_of_birth')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">User ID</label>
                        <input type="text" name="userID" class="form-control @error('userID') is-invalid @enderror"
                               value="{{ old('userID', $isEdit ? $student->userID : '') }}" placeholder="Staff/Admission No.">
                        @error('userID')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Citizenship</label>
                        <input type="text" name="citizenship" class="form-control @error('citizenship') is-invalid @enderror"
                               value="{{ old('citizenship', $isEdit ? $student->citizenship : 'Kenyan') }}">
                        @error('citizenship')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Ethnicity</label>
                        <select name="ethnicity" id="ethnicity" class="form-select select2-field @error('ethnicity') is-invalid @enderror">
                            <option value="">Select Ethnicity</option>
                            @foreach(config('ethnicities') as $ethnicity)
                                <option value="{{ $ethnicity }}" @selected(old('ethnicity', $isEdit ? $student->ethnicity : '') === $ethnicity)>
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
                                <option value="{{ $countyName }}" @selected(old('county', $isEdit ? $student->county : '') === $countyName)>
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

                <input type="hidden" id="currentSubCounty" value="{{ old('sub_county', $isEdit ? $student->sub_county : '') }}">
                <input type="hidden" id="currentWard" value="{{ old('ward', $isEdit ? $student->ward : '') }}">

                <h6 class="text-uppercase text-muted small mb-3">Academic Placement</h6>
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label class="form-label">Academic Year <span class="text-danger">*</span></label>
                        <select name="academic_year" id="AcademicYear"
                                class="form-control @error('academic_year') is-invalid @enderror">
                            <option value="">Select Academic Year</option>

                            @php
                                $years = array_reverse(range(2014, date('Y')));
                            @endphp

                            @foreach($years as $year)
                                <option value="{{ $year }}"
                                    @selected(old('academic_year', $currentEnrollment?->academic_year ?? '') == $year)>
                                    {{ $year }}
                                </option>
                            @endforeach
                        </select>
                        @error('academic_year')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Grade <span class="text-danger">*</span></label>
                        <select name="grade_level_id" id="gradeLevel"
                                data-streams-url="{{ route('grade-levels.streams') }}"
                                class="form-select select2-field @error('grade_level_id') is-invalid @enderror">
                            <option value="">Select Grade</option>
                            @foreach($gradeLevels as $gradeLevel)
                                <option value="{{ $gradeLevel->id }}" @selected(old('grade_level_id', $currentEnrollment->grade_level_id ?? '') === $gradeLevel->id)>
                                    {{ $gradeLevel->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('grade_level_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Stream</label>
                        <select name="stream_id" id="stream"
                                class="form-select select2-field @error('stream_id') is-invalid @enderror" @disabled(!($isEdit && ($currentEnrollment->grade_level_id ?? null)))>
                            <option value="">Select Grade First</option>
                        </select>
                        @error('stream_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                </div>
                <input type="hidden" id="currentStream" value="{{ old('stream_id', $currentEnrollment->stream_id ?? '') }}">

                <h6 class="text-uppercase text-muted small mb-3">Account & Contact</h6>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                               value="{{ old('email', $isEdit ? $student->email : '') }}" required>
                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Phone Number</label>
                        <input type="text" name="phone_number" class="form-control @error('phone_number') is-invalid @enderror"
                               value="{{ old('phone_number', $isEdit ? $student->phone_number : '') }}" placeholder="07XXXXXXXX">
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
                <a href="{{ route('users.index') }}" class="btn btn-sm btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-sm btn-primary px-4">{{ $isEdit ? 'Update User' : 'Create User' }}</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        $(function () {
            const $gradeLevel = $('#gradeLevel');
            const $stream = $('#stream');
            const streamsUrl = $gradeLevel.data('streams-url');
            const currentStream = $('#currentStream').val();

            function loadStreams(gradeLevelId, preselect = null) {
                if (!gradeLevelId) {
                    $stream.html('<option value="">Select Grade First</option>').prop('disabled', true).trigger('change');
                    return;
                }

                $.getJSON(streamsUrl, { grade_level: gradeLevelId }, function (streams) {
                    let options = '<option value="">Select Stream</option>';
                    streams.forEach(s => {
                        options += `<option value="${s.id}">${s.name}</option>`;
                    });
                    $stream.html(options).prop('disabled', false);
                    if (preselect) $stream.val(preselect);
                    $stream.trigger('change'); // needed for select2 to refresh display
                });
            }

            $gradeLevel.on('change', function () {
                loadStreams($(this).val());
            });

            // On page load (edit mode), restore stream for the already-selected grade
            if ($gradeLevel.val()) {
                loadStreams($gradeLevel.val(), currentStream);
            }
        });
    </script>
@endpush
