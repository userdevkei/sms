@extends('layouts.app')
@section('title', 'Time Slots')

@push('styles')
    <style>
        .slot-day-block { margin-bottom: 1.75rem; }
        .slot-day-block h2 {
            font-size: 0.95rem;
            color: #122744;
            border-bottom: 1px solid #122744;
            padding-bottom: 0.4rem;
            margin-bottom: 0.6rem;
        }
        .slot-badge { font-size: 0.72rem; padding: 0.15rem 0.5rem; border-radius: 3px; }
        .slot-badge.main { background: #DCF3E8; color: #0F5C4A; }
        .slot-badge.remedial { background: #FDF1DA; color: #8A5A00; }
        .slot-break { color: #9AA1AB; font-style: italic; }
    </style>
@endpush

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 mb-0">Time Slots</h1>
            <p class="text-muted small mb-0">Group: <strong>{{ $group->name }}</strong></p>
        </div>

        <div class="d-flex gap-2">
            @can('timetables.manage')
                <a href="{{ route('timeslots.bulk-create', ['group' => $group->id]) }}" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-grid-3x3-gap me-1"></i> Bulk Add
                </a>
                <a href="{{ route('timeslots.create', ['group' => $group->id]) }}" class="btn btn-primary btn-sm">
                    <i class="bi bi-plus-lg me-1"></i> Add Time Slot
                </a>
            @endcan
        </div>
    </div>

    @forelse(\App\Models\TimeSlot::DAYS as $dayNum => $dayName)
        @continue(! $slots->has($dayNum))
        <div class="slot-day-block">
            <h2>{{ $dayName }}</h2>
            <div class="card border-0 shadow-sm">
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                        <tr>
                            <th>#</th>
                            <th>Time</th>
                            <th>Label</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($slots[$dayNum]->sortBy('sequence') as $slot)
                            <tr>
                                <td>{{ $slot->sequence }}</td>
                                <td>
                                    @if($slot->is_break)
                                        <span class="slot-break">
                                            {{ \Illuminate\Support\Carbon::parse($slot->start_time)->format('H:i') }}
                                            – {{ \Illuminate\Support\Carbon::parse($slot->end_time)->format('H:i') }} (Break)
                                        </span>
                                    @else
                                        {{ \Illuminate\Support\Carbon::parse($slot->start_time)->format('H:i') }}
                                        – {{ \Illuminate\Support\Carbon::parse($slot->end_time)->format('H:i') }}
                                    @endif
                                </td>
                                <td>{{ $slot->label ?: '—' }}</td>
                                <td><span class="slot-badge {{ $slot->type }}">{{ ucfirst($slot->type) }}</span></td>
                                <td>
                                    <span class="badge {{ $slot->status === 'active' ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }} border">
                                        {{ ucfirst($slot->status) }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    @can('timetables.manage')
                                        <a href="{{ route('timeslots.edit', $slot->id) }}" class="btn btn-sm btn-outline-secondary">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-outline-danger delete-slot"
                                                data-config='{{ json_encode(["url" => route("timeslots.destroy", $slot->id)]) }}'>
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @empty
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center text-muted py-5">
                No time slots defined yet. <a href="{{ route('timeslots.create') }}">Add the first one</a>.
            </div>
        </div>
    @endforelse
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.delete-slot').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    const cfg = JSON.parse(this.dataset.config);
                    if (!confirm('Delete this time slot? This cannot be undone.')) return;

                    fetch(cfg.url, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json',
                        },
                    })
                        .then(r => r.json())
                        .then(res => {
                            if (res.success) window.location.reload();
                            else alert(res.message);
                        });
                });
            });
        });
    </script>
@endpush
