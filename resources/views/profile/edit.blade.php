@extends('layouts.app')
@section('title', 'Edit Profile')

@section('content')
    <div class="row justify-content-center">
        <div class="col-12 col-lg-8">
            <div class="d-flex align-items-center gap-2 mb-4">
                <i class="bi bi-person-gear fs-4 text-primary"></i>
                <h1 class="h4 mb-0">Edit Profile</h1>
            </div>

            <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="card border-0 shadow-sm">
                @csrf @method('PUT')
                <div class="card-body p-4 p-md-5">
                    @if($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                        </div>
                    @endif

                    <div class="d-flex align-items-center gap-3 mb-4">
                        <img src="{{ $user->avatar_url }}" alt="Avatar" class="rounded-circle" style="width:64px;height:64px;object-fit:cover;">
                        <div class="flex-grow-1">
                            <label class="form-label mb-1">Profile Photo</label>
                            <input type="file" name="avatar" class="form-control" accept="image/*">
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">First Name <span class="text-danger">*</span></label>
                            <input type="text" name="first_name" class="form-control" value="{{ old('first_name', $user->first_name) }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Middle Name</label>
                            <input type="text" name="middle_name" class="form-control" value="{{ old('middle_name', $user->middle_name) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Last Name <span class="text-danger">*</span></label>
                            <input type="text" name="last_name" class="form-control" value="{{ old('last_name', $user->last_name) }}" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control" value="{{ old('email', $user->email) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone Number</label>
                            <input type="text" name="phone_number" class="form-control" value="{{ old('phone_number', $user->phone_number) }}">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Gender</label>
                            <select name="gender" class="form-select">
                                <option value="">Select</option>
                                <option value="male" @selected(old('gender', $user->gender) === 'male')>Male</option>
                                <option value="female" @selected(old('gender', $user->gender) === 'female')>Female</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Date of Birth</label>
                            <input type="date" name="date_of_birth" class="form-control" value="{{ old('date_of_birth', $user->date_of_birth?->format('Y-m-d')) }}">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">County</label>
                            <input type="text" name="county" class="form-control" value="{{ old('county', $user->county) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Sub-County</label>
                            <input type="text" name="sub_county" class="form-control" value="{{ old('sub_county', $user->sub_county) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Ward</label>
                            <input type="text" name="ward" class="form-control" value="{{ old('ward', $user->ward) }}">
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-white border-0 p-4 pt-0 d-flex justify-content-between">
                    <a href="{{ route('profile.show') }}" class="btn btn-sm btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-sm btn-primary px-4">
                        <i class="bi bi-check2-circle me-1"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
