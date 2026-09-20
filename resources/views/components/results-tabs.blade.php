@props(['active'])
@php
    // A tab is shown only if the user passes the SAME check as that page's route middleware
    // (`can:...` in routes/web.php), so a tab is visible if and only if its page opens.
    $tabs = [
        'assessments'      => ['label' => 'Assessments',        'route' => 'results.assessments.index',      'permission' => 'results.view'],
        'assignments'      => ['label' => 'Subject Allocation', 'route' => 'results.assignments.index',      'permission' => 'results.view'],
        'assessment-types' => ['label' => 'Assessment Types',   'route' => 'results.assessment-types.index', 'permission' => 'results.view'],
        'report-cards'     => ['label' => 'Report Cards',       'route' => 'results.report-cards.index',     'permission' => 'results.report_cards.view'],
        'grading-bands'    => ['label' => 'Grading Bands',      'route' => 'results.grading-bands.index',    'permission' => 'curriculum.view'],
    ];

    $user    = auth()->user();
    $visible = collect($tabs)->filter(fn ($tab) => $user?->can($tab['permission']));
@endphp

@if ($visible->isNotEmpty())
    <ul class="nav nav-tabs mb-3">
        @foreach ($visible as $key => $tab)
            <li class="nav-item">
                <a class="nav-link {{ $active === $key ? 'active' : '' }}" href="{{ route($tab['route']) }}">{{ $tab['label'] }}</a>
            </li>
        @endforeach
    </ul>
@endif
