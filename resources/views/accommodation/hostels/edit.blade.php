{{-- resources/views/accommodation/hostels/edit.blade.php --}}
@extends('layouts.app')
@section('title', 'Edit Hostel')
@section('content')
    <div class="mb-3"><h1 class="h4 mb-1">Edit Hostel</h1></div>
    <form method="POST" action="{{ route('accommodation.hostels.update', $hostel->id) }}">
        @csrf @method('PUT')
        @include('accommodation.hostels._form')
    </form>
@endsection
@push('scripts')<script>$('.select2-field').select2({theme:'bootstrap-5', width:'100%'});</script>@endpush
