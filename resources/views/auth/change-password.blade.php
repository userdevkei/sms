{{-- resources/views/auth/change-password.blade.php --}}
@extends('layouts.app')
@section('title', 'Change Password')

@section('content')
    <div class="row justify-content-center">
        <div class="col-12 col-lg-6">
            <div class="d-flex align-items-center gap-2 mb-4">
                <i class="bi bi-key fs-4 text-primary"></i>
                <h1 class="h4 mb-0">Change Password</h1>
            </div>

            <form method="POST" action="{{ route('password.change.update') }}" class="card border-0 shadow-sm">
                @csrf @method('PUT')
                <div class="card-body p-4">
                    @if($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="mb-3">
                        <label class="form-label">Current Password <span class="text-danger">*</span></label>
                        <input type="password" name="current_password" class="form-control" required autocomplete="current-password">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">New Password <span class="text-danger">*</span></label>
                        <input type="password" name="password" class="form-control" required minlength="8" autocomplete="new-password">
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Confirm New Password <span class="text-danger">*</span></label>
                        <input type="password" name="password_confirmation" class="form-control" required minlength="8" autocomplete="new-password">
                    </div>
                </div>
                <div class="card-footer bg-white border-0 p-4 pt-0 d-flex justify-content-end">
                    <button type="submit" class="btn btn-sm btn-primary px-4">
                        <i class="bi bi-check2-circle me-1"></i> Update Password
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
