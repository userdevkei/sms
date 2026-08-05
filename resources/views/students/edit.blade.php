{{-- resources/views/users/edit.blade.php --}}
@extends('layouts.app')
@section('title', 'Edit Student')

@section('content')
    <div class="mb-3">
        <h1 class="h4 mb-1">Edit Student</h1>
        <p class="text-muted mb-0">Update {{ $student->full_name }}'s details.</p>
    </div>

    <form method="POST" action="{{ route('students.update', $student->id) }}" enctype="multipart/form-data" id="userForm">
        @csrf
        @method('PUT')
        @include('students._form')
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
