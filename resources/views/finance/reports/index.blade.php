{{-- Adjust the layout / section names to match your app layout. --}}
@extends('layouts.app')

@section('title', 'Finance reports')

@section('content')
    <div class="container-fluid py-3">
        <h4 class="mb-1">Finance reports</h4>
        <p class="text-muted">Choose a report. You can refine it with filters and export it to Excel or PDF.</p>

        <div class="row g-3">
            @foreach ([
                ['Debtors by grade',      'Outstanding fees for each grade, with subtotals.',                    route('finance.reports.debtors', ['group_by' => 'grade'])],
                ['Debtors by stream',     'Outstanding fees for each stream, with subtotals.',                   route('finance.reports.debtors', ['group_by' => 'stream'])],
                ['Debtors by balance %',  'Find students who still owe 25%, 50%, 75% or more of their fees.',   route('finance.reports.debtors', ['min_pct' => 50])],
                ['All debtors',           'Every student with a balance, with all filters available.',           route('finance.reports.debtors')],
            ] as [$title, $desc, $url])
                <div class="col-md-6 col-xl-3">
                    <a href="{{ $url }}" class="card h-100 shadow-sm text-decoration-none text-body">
                        <div class="card-body">
                            <h6 class="card-title mb-1">{{ $title }}</h6>
                            <p class="card-text small text-muted mb-0">{{ $desc }}</p>
                        </div>
                    </a>
                </div>
            @endforeach
        </div>
    </div>
@endsection
