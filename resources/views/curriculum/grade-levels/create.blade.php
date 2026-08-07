{{-- resources/views/curriculum/grade-levels/create.blade.php --}}
@extends('layouts.app')
@section('title', 'Add Grade Level')
@section('content')
    <div class="mb-3"><h1 class="h4 mb-1">Add Grade Level</h1></div>
    <form method="POST" action="{{ route('curriculum.grade-levels.store') }}">
        @csrf
        @include('curriculum.grade-levels._form')
    </form>
@endsection
@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0/dist/css/select2.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">
@endpush
@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0/dist/js/select2.min.js"></script>
    <script>$('.select2-field').select2({theme:'bootstrap-5', width:'100%'});</script>
@endpush
