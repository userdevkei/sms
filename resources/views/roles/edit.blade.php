{{-- resources/views/roles/edit.blade.php --}}
@extends('layouts.app')
@section('title', 'Edit Role')

@section('content')
    <div class="mb-3">
        <h1 class="h4 mb-1">Edit Role</h1>
        <p class="text-muted mb-0">Update "{{ $role->name }}" and its permissions.</p>
    </div>

    <form method="POST" action="{{ route('roles.update', $role->id) }}" id="roleForm">
        @csrf
        @method('PUT')
        @include('roles._form')
    </form>
@endsection

@push('scripts')
    <script src="{{ asset('js/role-form.js') }}"></script>
@endpush
