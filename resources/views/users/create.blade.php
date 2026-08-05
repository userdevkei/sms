{{-- resources/views/users/create.blade.php --}}
@extends('layouts.app')
@section('title', 'Add User')

@section('content')
    <div class="mb-3">
        <h1 class="h4 mb-1">Add New User</h1>
        <p class="text-muted mb-0">Create a new account and assign their role(s).</p>
    </div>

    <form method="POST" action="{{ route('users.store') }}" enctype="multipart/form-data" id="userForm">
        @csrf
        @include('users._form')
    </form>
@endsection

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0/dist/css/select2.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0/dist/js/select2.min.js"></script>
    <script src="{{ asset('js/user-form.js') }}"></script>
    <script src="{{ asset('js/location-select.js') }}"></script>
@endpush
