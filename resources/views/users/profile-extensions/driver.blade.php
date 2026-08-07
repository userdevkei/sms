@php
    $driver = \App\Models\Driver::where('user_id', $user->id)->first();
    $currentAssignment = $driver?->routeAssignments()->where('status', 'active')->with(['route', 'vehicle'])->first();
@endphp

@if($driver)
    <div class="profile-page">
        <div class="kv-card kv-panel mt-4">
            <div class="kv-panel-head">
                <span class="kv-panel-icon"><i class="bi bi-bus-front"></i></span>
                <h3>Driver Details</h3>
            </div>
            <div class="kv-panel-body">
                <div class="kv-row">
                    <span class="kv-label"><i class="bi bi-card-text"></i> License Number</span>
                    <span class="kv-value kv-mono">{{ $driver->license_number }}</span>
                </div>
                <div class="kv-row">
                    <span class="kv-label"><i class="bi bi-award"></i> License Class</span>
                    <span class="kv-value">{{ $driver->license_class ?: '—' }}</span>
                </div>
                <div class="kv-row">
                    <span class="kv-label"><i class="bi bi-calendar-x"></i> License Expiry</span>
                    <span class="kv-value">
                    {{ $driver->license_expiry?->format('d M Y') ?? '—' }}
                        @if($driver->license_expiring_soon)
                            <span class="kv-pill kv-pill-inactive" style="margin-left:.4rem;"><i class="bi bi-exclamation-triangle"></i> Expiring Soon</span>
                        @endif
                </span>
                </div>

                @if($currentAssignment)
                    <div class="kv-row">
                        <span class="kv-label"><i class="bi bi-signpost"></i> Assigned Route</span>
                        <span class="kv-value">{{ $currentAssignment->route?->name ?? '—' }}</span>
                    </div>
                    <div class="kv-row">
                        <span class="kv-label"><i class="bi bi-truck"></i> Assigned Vehicle</span>
                        <span class="kv-value">{{ $currentAssignment->vehicle?->registration_number ?? '—' }}</span>
                    </div>
                    <div class="kv-row kv-row-last">
                        <span class="kv-label"><i class="bi bi-calendar-range"></i> Term</span>
                        <span class="kv-value">{{ $currentAssignment->term ?: '—' }}</span>
                    </div>
                @else
                    <div class="kv-row kv-row-last">
                        <span class="kv-label"><i class="bi bi-signpost"></i> Current Assignment</span>
                        <span class="kv-value text-muted fst-italic" style="font-weight:500;">Not currently assigned to a route</span>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endif
