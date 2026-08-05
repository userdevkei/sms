{{-- resources/views/transport/routes/create.blade.php --}}
@extends('layouts.app')
@section('title', 'Edit Route')

@section('content')
    <div class="mb-3">
        <h1 class="h4 mb-1">Edit {{ $route->name }} Route</h1>
        <p class="text-muted mb-0">Define the route and its pick-up/drop-off points with fares.</p>
    </div>

    @if($errors->any())
        <div class="alert alert-danger">
            <strong>Please fix the following:</strong>
            <ul class="mb-0 mt-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('transport.transport-routes.update', $route->id) }}" id="routeForm">
        @method('PUT')
        @csrf
        @include('transport.routes._form')
    </form>
@endsection

@push('scripts')
    <script src="{{ asset('js/route-form.js') }}"></script>
@endpush
