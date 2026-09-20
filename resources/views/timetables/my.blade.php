@extends('layouts.app')

@section('content')
    @php
        $dayNames = [1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday'];
        $today = now()->dayOfWeekIso;
    @endphp

    <style>
        .tt-toggle { cursor: pointer; }
        .tt-toggle .tt-caret { transition: transform .2s; }
        .tt-toggle:not(.collapsed) .tt-caret { transform: rotate(180deg); }

        .tt-grid { min-width: max-content; margin-bottom: 0; }
        .tt-grid th, .tt-grid td { vertical-align: middle; }
        .tt-grid thead th { background: #f8fafc; font-weight: 600; font-size: .85rem; white-space: nowrap; text-align: center; }
        .tt-grid .tt-day { position: sticky; left: 0; z-index: 1; min-width: 120px; background: #f8fafc; text-align: left; font-weight: 600; }
        .tt-grid thead .tt-day { z-index: 2; }
        .tt-grid .tt-today { box-shadow: inset 3px 0 0 #0d6efd; }
        .tt-cell { min-width: 150px; padding: .4rem !important; }

        /* Grade colour palette: --tt-bg = card tint, --tt-bd = accent */
        .tt-c0  { --tt-bg: #eef4ff; --tt-bd: #0d6efd; }
        .tt-c1  { --tt-bg: #e9f7ef; --tt-bd: #198754; }
        .tt-c2  { --tt-bg: #fff3e6; --tt-bd: #fd7e14; }
        .tt-c3  { --tt-bg: #f3ecff; --tt-bd: #6f42c1; }
        .tt-c4  { --tt-bg: #e6f7f8; --tt-bd: #0aa2c0; }
        .tt-c5  { --tt-bg: #fdeaf3; --tt-bd: #d63384; }
        .tt-c6  { --tt-bg: #fff9e0; --tt-bd: #e0a800; }
        .tt-c7  { --tt-bg: #fdecec; --tt-bd: #dc3545; }
        .tt-c8  { --tt-bg: #eceefe; --tt-bd: #4c5bd4; }
        .tt-c9  { --tt-bg: #f1f7e3; --tt-bd: #6b8e23; }
        .tt-c10 { --tt-bg: #eef1f4; --tt-bd: #495057; }
        .tt-c11 { --tt-bg: #f6eee8; --tt-bd: #8d5a34; }

        .tt-lesson { text-align: left; background: var(--tt-bg, #eef4ff); border-left: 3px solid var(--tt-bd, #0d6efd); border-radius: .35rem; padding: .35rem .5rem; }

        .tt-legend { display: flex; flex-wrap: wrap; gap: .4rem; padding: .75rem 1rem; border-bottom: 1px solid var(--bs-border-color, #dee2e6); }
        .tt-chip { display: inline-flex; align-items: center; gap: .4rem; padding: .15rem .6rem; border-radius: 999px; font-size: .75rem; font-weight: 600; background: var(--tt-bg); border: 1px solid var(--tt-bd); }
        .tt-chip i { width: 8px; height: 8px; border-radius: 50%; background: var(--tt-bd); }
        .tt-lesson + .tt-lesson { margin-top: .3rem; }
        .tt-subject { font-weight: 600; font-size: .85rem; line-height: 1.25; }
        .tt-meta { font-size: .75rem; color: #6c757d; line-height: 1.25; }
        .tt-free { color: #ced4da; }
    </style>

    <div class="container-fluid">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <h4 class="mb-0">{{ $title }}</h4>

            <div class="d-flex flex-wrap align-items-center gap-2">
                @if ($isClassTeacher && $action === $ownUrl)
                    <a href="{{ $classUrl }}" class="btn btn-sm btn-outline-primary">My Class Timetable</a>
                @elseif ($isClassTeacher)
                    <a href="{{ $ownUrl }}" class="btn btn-sm btn-outline-primary">My Own Timetable</a>
                @endif

                @if ($timetables->isNotEmpty())
                    <form method="GET" action="{{ $action }}" class="d-flex gap-2">
                        @if ($streams->count() > 1)
                            <select name="stream" class="form-select form-select-sm" onchange="this.form.submit()">
                                @foreach ($streams as $s)
                                    <option value="{{ $s->id }}" @selected($selectedStream?->id === $s->id)>
                                        {{ trim($s->gradeLevel?->name.' '.$s->name) }}
                                    </option>
                                @endforeach
                            </select>
                        @endif

                        <select name="timetable" class="form-select form-select-sm" onchange="this.form.submit()">
                            @foreach ($timetables as $t)
                                <option value="{{ $t->id }}" @selected($selectedTimetable?->id === $t->id)>{{ $t->name }}</option>
                            @endforeach
                        </select>
                    </form>
                @endif
            </div>
        </div>

        @forelse ($sections as $i => $section)
            @php
                $grid   = $section['grid'];
                $panel  = 'tt-panel-'.$i;
                $isOpen = $i === 0;   // newest timetable open, the rest collapsed
            @endphp

            <div class="card mb-3">
                <button type="button"
                        class="tt-toggle card-header d-flex justify-content-between align-items-center w-100 border-0 text-start fw-semibold {{ $isOpen ? '' : 'collapsed' }}"
                        data-bs-toggle="collapse"
                        data-bs-target="#{{ $panel }}"
                        aria-expanded="{{ $isOpen ? 'true' : 'false' }}"
                        aria-controls="{{ $panel }}">
                    <span>{{ $section['label'] }}</span>
                    <svg class="tt-caret" width="16" height="16" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                        <path d="M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z"/>
                    </svg>
                </button>

                <div id="{{ $panel }}" class="collapse {{ $isOpen ? 'show' : '' }}">
                    <div class="card-body p-0">
                        @if ($grid['columns']->isEmpty())
                            <p class="text-muted p-3 mb-0">No lessons scheduled.</p>
                        @else
                            @if ($grid['legend']->count() > 1)
                                <div class="tt-legend">
                                    @foreach ($grid['legend'] as $g)
                                        <span class="tt-chip tt-c{{ $g['color'] }}"><i></i>{{ $g['name'] }}</span>
                                    @endforeach
                                </div>
                            @endif

                            <div class="table-responsive">
                                <table class="table table-bordered tt-grid">
                                    <thead>
                                    <tr>
                                        <th class="tt-day">Day / Time</th>
                                        @foreach ($grid['columns'] as $col)
                                            <th>{{ $col['start'] }}–{{ $col['end'] }}</th>
                                        @endforeach
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach ($grid['days'] as $day)
                                        <tr>
                                            <th class="tt-day {{ $day === $today ? 'tt-today' : '' }}">
                                                {{ $dayNames[$day] ?? ucfirst($day) }}
                                                @if ($day === $today)
                                                    <span class="badge bg-primary ms-1">Today</span>
                                                @endif
                                            </th>

                                            @foreach ($grid['columns'] as $col)
                                                @php $items = $grid['cells'][$day][$col['key']] ?? collect(); @endphp
                                                <td class="tt-cell">
                                                    @forelse ($items as $e)
                                                        @php
                                                            $parts = [];
                                                            if ($showClass) {
                                                                $parts[] = $e->streams
                                                                    ->map(fn ($s) => trim($s->gradeLevel?->name.' '.$s->name))
                                                                    ->join(', ');
                                                            }
                                                            if ($showTeacher && $e->teacher) {
                                                                $parts[] = $e->teacher->full_name;
                                                            }
                                                        @endphp
                                                        <div class="tt-lesson tt-c{{ $e->tt_color }}">
                                                            <div class="tt-subject">{{ $e->learningArea?->name }}</div>
                                                            @if ($parts)
                                                                <div class="tt-meta">{{ implode(' · ', $parts) }}</div>
                                                            @endif
                                                        </div>
                                                    @empty
                                                        <span class="tt-free">—</span>
                                                    @endforelse
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="alert alert-info">No published timetable available yet.</div>
        @endforelse
    </div>
@endsection
