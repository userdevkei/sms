@props(['active'])
@php
    $tabs = [
        'assessments'       => ['label' => 'Assessments', 'route' => 'results.assessments.index'],
        'assignments'       => ['label' => 'Teacher Assignments', 'route' => 'results.assignments.index'],
        'assessment-types'  => ['label' => 'Assessment Types', 'route' => 'results.assessment-types.index'],
        'report-cards'      => ['label' => 'Report Cards', 'route' => 'results.report-cards.index'],
        'grading-bands'     => ['label' => 'Grading Bands', 'route' => 'results.grading-bands.index'],
    ];
@endphp
<ul class="nav nav-tabs mb-3">
    @foreach($tabs as $key => $tab)
        <li class="nav-item"><a class="nav-link {{ $active === $key ? 'active' : '' }}" href="{{ route($tab['route']) }}">{{ $tab['label'] }}</a></li>
    @endforeach
</ul>
