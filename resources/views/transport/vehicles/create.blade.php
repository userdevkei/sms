{{-- resources/views/users/create.blade.php --}}
@extends('layouts.app')
@section('title', 'Add Vehicle')

@section('content')
    <div class="mb-3">
        <h1 class="h4 mb-1">Add New Vehicle</h1>
        <p class="text-muted mb-0">Create a new vehicle in your fleet.</p>
    </div>

    <form method="POST" action="{{ route('transport.vehicles.store') }}" enctype="multipart/form-data" id="userForm">
        @csrf
        @include('transport.vehicles._form')
    </form>
@endsection

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0/dist/css/select2.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0/dist/js/select2.min.js"></script>
@endpush
