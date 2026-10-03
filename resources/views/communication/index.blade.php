{{-- resources/views/communication/index.blade.php --}}
@extends('layouts.app')
@section('title', 'Sent & Scheduled Messages')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h4 mb-0"><i class="bi bi-send me-2"></i>Sent &amp; Scheduled Messages</h1>
        <a href="{{ route('communication.compose') }}" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg me-1"></i> Send Message</a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover table-sm table-striped fs-sm w-100" id="commsTable">
                    <thead><tr><th>#</th><th>Channel</th><th>Subject / Body</th><th>Recipients</th><th>Status</th><th>Send At</th><th>Repeats</th></tr></thead>
                    <tbody>
                    @foreach($communications as $c)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td class="text-capitalize">{{ $c->channel }}</td>
                            <td>{{ \Illuminate\Support\Str::limit($c->subject ?? $c->body, 60) }}</td>
                            <td>{{ $c->recipients->count() }}</td>
                            <td>
                                <span class="badge bg-{{ ['sent'=>'success','failed'=>'danger','scheduled'=>'info','sending'=>'warning'][$c->status] ?? 'secondary' }}-subtle text-{{ ['sent'=>'success','failed'=>'danger','scheduled'=>'info','sending'=>'warning'][$c->status] ?? 'secondary' }}">
                                    {{ ucfirst($c->status) }}
                                </span>
                            </td>
                            <td>{{ $c->send_at?->format('d M Y, H:i') ?? '—' }}</td>
                            <td>{{ $c->recurrence_rule ? ucfirst($c->recurrence_rule) : '—' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
@endpush
@push('scripts')
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>

    <script>
        $('#commsTable').DataTable({ order: [[0, 'asc']], pageLength: 50, columnDefs: [{ targets: -1, orderable: false }] });
    </script>
@endpush
