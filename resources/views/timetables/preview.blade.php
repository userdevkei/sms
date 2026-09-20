@extends('layouts.app')
@section('title', 'Timetable Preview')

@push('styles')
    <style>
        .tt-grid { border-collapse: collapse; width: 100%; font-size: 0.85rem; }
        .tt-grid th, .tt-grid td { border: 1px solid #E4E1E1; padding: 0.4rem 0.5rem; vertical-align: top; }
        .tt-grid th { background: #122744; color: #fff; font-weight: 500; font-size: 0.78rem; }
        .tt-cell-subject { font-weight: 600; color: #122744; }
        .tt-cell-teacher { font-size: 0.75rem; color: #7A8391; }
        .tt-cell.is-double { background: #FEF6E7; }
        .tt-cell.is-merged { background: #E9F5EF; }
        .status-pill { padding: 0.2rem 0.65rem; border-radius: 3px; font-size: 0.78rem; }
        .status-pill.draft { background: #E9EAEC; color: #4B5563; }
        .status-pill.approved { background: #FDF1DA; color: #8A5A00; }
        .status-pill.published { background: #DCF3E8; color: #0F5C4A; }
        .conflict-panel { background: #FBE2E1; border-left: 3px solid #B3261E; padding: 1rem 1.25rem; margin-bottom: 1.25rem; }
        .conflict-panel li { font-size: 0.88rem; }
    </style>
@endpush

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 mb-1">{{ $timetable->name }}</h1>
            <span class="status-pill {{ $timetable->status }}">{{ ucfirst($timetable->status) }}</span>
            <span class="text-muted small ms-2">{{ $timetable->gradeLevels->pluck('name')->join(', ') }}</span>
        </div>
        <div class="d-flex gap-2">
            @if($timetable->status === 'draft')
                <form method="POST" action="{{ route('timetables.generate', $timetable->id) }}">
                    @csrf
                    <button class="btn btn-primary btn-sm">
                        <i class="bi bi-magic me-1"></i> {{ $timetable->generated_at ? 'Regenerate' : 'Generate' }}
                    </button>
                </form>
                @can('timetables.approve')
                    <form method="POST" action="{{ route('timetables.approve', $timetable->id) }}">
                        @csrf
                        <button class="btn btn-outline-success btn-sm">Approve</button>
                    </form>
                @endcan
            @elseif($timetable->status === 'approved')
                @can('timetables.publish')
                    <form method="POST" action="{{ route('timetables.publish', $timetable->id) }}">
                        @csrf
                        <button class="btn btn-success btn-sm">Publish</button>
                    </form>
                @endcan
            @endif
        </div>
    </div>

    @if(!empty($timetable->generation_notes['conflicts']))
        <div class="conflict-panel">
            <strong>{{ count($timetable->generation_notes['conflicts']) }} item(s) could not be placed:</strong>
            <ul class="mb-0 mt-2">
                @foreach($timetable->generation_notes['conflicts'] as $conflict)
                    <li>{{ $conflict }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <ul class="nav nav-tabs mb-3" id="streamTabs">
        @foreach($streams as $i => $stream)
            <li class="nav-item">
                <button class="nav-link {{ $i === 0 ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#stream-{{ $stream->id }}">
                    {{ $stream->full_name }}
                </button>
            </li>
        @endforeach
    </ul>

    <div class="tab-content">
        @foreach($streams as $i => $stream)
            <div class="tab-pane {{ $i === 0 ? 'show active' : '' }}" id="stream-{{ $stream->id }}">
                <div class="table-responsive">
                    <table class="tt-grid">
                        <thead>
                        <tr>
                            <th style="width:110px">Time</th>
                            @foreach(\App\Models\TimeSlot::DAYS as $num => $dayName)
                                @if($slots->has($num))
                                    <th>{{ $dayName }}</th>
                                @endif
                            @endforeach
                        </tr>
                        </thead>
                        <tbody>
                        @php
                            $maxSequence = $slots->flatten()->max('sequence');
                            $slotsBySequence = $slots->flatten()->groupBy('sequence');
                        @endphp
                        @for($seq = 1; $seq <= $maxSequence; $seq++)
                            @php $rowSlots = $slotsBySequence->get($seq, collect()); @endphp
                            @if($rowSlots->isNotEmpty())
                                <tr>
                                    <td class="text-muted small">
                                        {{ \Illuminate\Support\Carbon::parse($rowSlots->first()->start_time)->format('H:i') }}
                                        –
                                        {{ \Illuminate\Support\Carbon::parse($rowSlots->first()->end_time)->format('H:i') }}
                                    </td>
                                    @foreach(\App\Models\TimeSlot::DAYS as $num => $dayName)
                                        @continue(! $slots->has($num))
                                        @php
                                            $slot = $rowSlots->firstWhere('day_of_week', $num);
                                            $entry = $slot ? $entries->first(fn ($e) => $e->time_slot_id === $slot->id && $e->streams->contains('id', $stream->id)) : null;
                                        @endphp
                                        <td class="tt-cell {{ $entry?->is_double ? 'is-double' : '' }} {{ $entry && $entry->streams->count() > 1 ? 'is-merged' : '' }}">
                                            @if($slot?->is_break)
                                                <span class="text-muted small">— Break —</span>
                                            @elseif($entry)
                                                <div class="tt-cell-subject">{{ $entry->learningArea->name }}</div>
                                                <div class="tt-cell-teacher">{{ trim($entry->teacher->first_name.' '.$entry->teacher->last_name) }}</div>
                                                @if($entry->streams->count() > 1)
                                                    <div class="tt-cell-teacher">with {{ $entry->streams->where('id', '!=', $stream->id)->pluck('full_name')->join(', ') }}</div>
                                                @endif
                                            @else
                                                <span class="text-muted small">—</span>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endif
                        @endfor
                        </tbody>
                    </table>
                </div>
            </div>
        @endforeach
    </div>
@endsection
