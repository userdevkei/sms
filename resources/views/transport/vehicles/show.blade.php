@extends('layouts.app')
@section('title', $vehicle->registration_number)

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 mb-1">{{ $vehicle->registration_number }}</h1>
            <p class="text-muted mb-0">{{ $vehicle->make }} {{ $vehicle->model }} — {{ $vehicle->capacity }} seats</p>
        </div>
        <a href="{{ route('transport.vehicles.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Back</a>
    </div>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="text-uppercase text-muted small mb-3">Compliance</h6>
                    <p class="mb-1"><strong>Insurance Expiry:</strong> {{ $vehicle->insurance_expiry?->format('d M Y') ?? '—' }}
                        @if($vehicle->insurance_expiring_soon)<span class="badge bg-warning-subtle text-warning">Expiring Soon</span>@endif
                    </p>
                    <p class="mb-1"><strong>Inspection Expiry:</strong> {{ $vehicle->inspection_expiry?->format('d M Y') ?? '—' }}
                        @if($vehicle->inspection_expiring_soon)<span class="badge bg-warning-subtle text-warning">Expiring Soon</span>@endif
                    </p>
                    <p class="mb-0"><strong>Logbook No.:</strong> {{ $vehicle->logbook_number ?: '—' }}</p>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="text-uppercase text-muted small mb-0">Maintenance / Service Log</h6>
                        @can('transport.manage')
                            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addMaintenanceModal">
                                <i class="bi bi-plus-lg me-1"></i> Add Log
                            </button>
                        @endcan
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead><tr><th>Date</th><th>Description</th><th>Cost</th><th>Odometer</th><th>Next Service</th><th>Serviced By</th></tr></thead>
                            <tbody>
                            @forelse($vehicle->maintenanceLogs as $log)
                                <tr>
                                    <td>{{ $log->service_date->format('d M Y') }}</td>
                                    <td>{{ $log->description }}</td>
                                    <td>{{ $log->cost ? 'KES ' . number_format($log->cost, 0) : '—' }}</td>
                                    <td>{{ $log->odometer_reading ? number_format($log->odometer_reading) . ' km' : '—' }}</td>
                                    <td>{{ $log->next_service_date?->format('d M Y') ?? '—' }}</td>
                                    <td>{{ $log->serviced_by ?: '—' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-muted py-3">No maintenance records yet.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @can('transport.manage')
        <div class="modal fade" id="addMaintenanceModal" tabindex="-1">
            <div class="modal-dialog">
                <form method="POST" action="{{ route('transport.vehicles.maintenance.store', $vehicle->id) }}">
                    @csrf
                    <div class="modal-content">
                        <div class="modal-header"><h5 class="modal-title">Add Maintenance Log</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                        <div class="modal-body">
                            <div class="mb-2"><label class="form-label">Service Date</label><input type="date" name="service_date" class="form-control" required></div>
                            <div class="mb-2"><label class="form-label">Description</label><input type="text" name="description" class="form-control" required></div>
                            <div class="row g-2 mb-2">
                                <div class="col-6"><label class="form-label">Cost (KES)</label><input type="number" step="0.01" name="cost" class="form-control"></div>
                                <div class="col-6"><label class="form-label">Odometer (km)</label><input type="number" name="odometer_reading" class="form-control"></div>
                            </div>
                            <div class="mb-2"><label class="form-label">Next Service Date</label><input type="date" name="next_service_date" class="form-control"></div>
                            <div class="mb-0"><label class="form-label">Serviced By</label><input type="text" name="serviced_by" class="form-control" placeholder="Garage/mechanic name"></div>
                        </div>
                        <div class="modal-footer"><button type="submit" class="btn btn-primary">Save Log</button></div>
                    </div>
                </form>
            </div>
        </div>
    @endcan
@endsection
