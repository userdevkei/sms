{{-- resources/views/transport/drivers/create.blade.php --}}
@extends('layouts.app')
@section('title', 'Update Driver License Details')

@section('content')
    <div class="mb-3">
        <h1 class="h4 mb-1">Add Driver License Details</h1>
        <p class="text-muted mb-0">Complete license information for a user already assigned the Driver role.</p>
    </div>

    <form method="post" action="{{ route('transport.drivers.update', $driver->id) }}" id="driverForm">
        @csrf
        @include('transport.drivers._form_edit')
    </form>
@endsection

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0/dist/css/select2.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0/dist/js/select2.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const $userSelect = $('#userSelect');
            if ($userSelect.length) {
                $userSelect.select2({ theme: 'bootstrap-5', width: '100%' });
            }
        });
    </script>
@endpush
