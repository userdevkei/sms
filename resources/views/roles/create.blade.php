{{-- resources/views/roles/create.blade.php --}}
@extends('layouts.app')
@section('title', 'Add Role')

@section('content')
    <div class="mb-3">
        <h1 class="h4 mb-1">Add New Role</h1>
        <p class="text-muted mb-0">Define a role and choose what it can access.</p>
    </div>

    <form method="POST" action="{{ route('roles.store') }}" id="roleForm">
        @csrf
        @include('roles._form')
    </form>
@endsection

@push('scripts')
    <script src="{{ asset('js/role-form.js') }}"></script>
@endpush
