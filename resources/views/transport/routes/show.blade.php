{{-- resources/views/transport/routes/show.blade.php --}}
@extends('layouts.app')
@section('title', $route->name)

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-start mb-3 gap-2">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <h1 class="h4 mb-0">{{ $route->name }}</h1>
                @if($route->code)
                    <span class="badge bg-secondary-subtle text-secondary">{{ $route->code }}</span>
                @endif
                <span class="badge bg-{{ $route->status === 'active' ? 'success' : 'secondary' }}-subtle text-{{ $route->status === 'active' ? 'success' : 'secondary' }} text-capitalize">
                {{ $route->status }}
            </span>
            </div>
            @if($route->description)
                <p class="text-muted mb-0">{{ $route->description }}</p>
            @endif
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('transport.transport-routes.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back
            </a>
            @can('transport.manage')
                <a href="{{ route('transport.transport-routes.edit', $route->id) }}" class="btn btn-sm btn-primary">
                    <i class="bi bi-pencil me-1"></i> Edit Route
                </a>
            @endcan
        </div>
    </div>

    <div class="row g-4">
        {{-- Stops --}}
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="text-uppercase text-muted small mb-3">Pick-up / Drop-off Points & Fare</h6>

                    @if($route->stops->isEmpty())
                        <p class="text-muted small fst-italic mb-0">No stops have been added to this route yet.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead>
                                <tr>
                                    <th style="width: 40px;">#</th>
                                    <th>Stop Name</th>
                                    <th>Landmark</th>
                                    <th class="text-end">Fare</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($route->stops as $stop)
                                    <tr>
                                        <td>
                                            <span class="stop-row-number d-inline-flex align-items-center justify-content-center"
                                                  style="width: 24px; height: 24px; font-size: 0.72rem;">
                                                {{ $stop->sequence }}
                                            </span>
                                        </td>
                                        <td>{{ $stop->name }}</td>
                                        <td class="text-muted">{{ $stop->landmark_description ?: '—' }}</td>
                                        <td class="text-end fw-semibold">KES {{ number_format($stop->fare, 0) }}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                                <tfoot>
                                <tr>
                                    <td colspan="3" class="text-muted small">Fare range across all stops</td>
                                    <td class="text-end small fw-semibold">
                                        KES {{ number_format($route->stops->min('fare'), 0) }}
                                        @if($route->stops->min('fare') != $route->stops->max('fare'))
                                            – {{ number_format($route->stops->max('fare'), 0) }}
                                        @endif
                                    </td>
                                </tr>
                                </tfoot>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Current + past assignments --}}
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <h6 class="text-uppercase text-muted small mb-3">Current Assignment</h6>
                    @php $current = $route->assignments->firstWhere('status', 'active'); @endphp

                    @if($current)
                        <div class="d-flex align-items-center gap-3 mb-2">
                            <div class="app-stat-card d-flex align-items-center gap-2 p-2 flex-grow-1" style="border-radius: 10px;">
                            <span class="stat-icon" style="width: 34px; height: 34px; font-size: 0.95rem;">
                                <i class="bi bi-truck"></i>
                            </span>
                                <div>
                                    <div class="text-muted small">Vehicle</div>
                                    <div class="fw-semibold">{{ $current->vehicle?->registration_number ?? '—' }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div class="app-stat-card d-flex align-items-center gap-2 p-2 flex-grow-1" style="border-radius: 10px;">
                            <span class="stat-icon" style="width: 34px; height: 34px; font-size: 0.95rem; background: var(--brand-secondary);">
                                <i class="bi bi-person-badge"></i>
                            </span>
                                <div>
                                    <div class="text-muted small">Driver</div>
                                    <div class="fw-semibold">{{ $current->driver?->full_name ?? '—' }}</div>
                                </div>
                            </div>
                        </div>
                        <dl class="row small mb-0">
                            <dt class="col-5 text-muted">Term</dt>
                            <dd class="col-7">{{ $current->term ?: '—' }}</dd>
                            <dt class="col-5 text-muted">Start Date</dt>
                            <dd class="col-7">{{ $current->start_date?->format('d M Y') ?? '—' }}</dd>
                            <dt class="col-5 text-muted">End Date</dt>
                            <dd class="col-7">{{ $current->end_date?->format('d M Y') ?? '—' }}</dd>
                        </dl>
                        @can('transport.manage')
                            <a href="{{ route('transport.route-assignments.index') }}" class="btn btn-sm btn-outline-secondary w-100 mt-2">
                                <i class="bi bi-arrow-repeat me-1"></i> Manage Assignments
                            </a>
                        @endcan
                    @else
                        <p class="text-muted small fst-italic mb-3">No vehicle or driver currently assigned to this route.</p>
                        @can('transport.manage')
                            <a href="{{ route('transport.route-assignments.index') }}" class="btn btn-sm btn-primary w-100">
                                <i class="bi bi-plus-lg me-1"></i> Assign Vehicle & Driver
                            </a>
                        @endcan
                    @endif
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="text-uppercase text-muted small mb-3">Assignment History</h6>

                    @php $pastAssignments = $route->assignments->where('status', 'ended')->sortByDesc('end_date'); @endphp

                    @if($pastAssignments->isEmpty())
                        <p class="text-muted small fst-italic mb-0">No past assignments recorded.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm mb-0">
                                <thead>
                                <tr><th>Vehicle</th><th>Driver</th><th>Term</th><th>Ended</th></tr>
                                </thead>
                                <tbody>
                                @foreach($pastAssignments as $past)
                                    <tr>
                                        <td>{{ $past->vehicle?->registration_number ?? '—' }}</td>
                                        <td>{{ $past->driver?->full_name ?? '—' }}</td>
                                        <td>{{ $past->term ?: '—' }}</td>
                                        <td>{{ $past->end_date?->format('d M Y') ?? '—' }}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
