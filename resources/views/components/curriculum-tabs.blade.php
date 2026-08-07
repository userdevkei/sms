@props(['active'])

@php
    $tabs = [
        'education-levels' => ['label' => 'Education Levels', 'route' => 'curriculum.education-levels.index'],
        'grade-levels'     => ['label' => 'Grade Levels', 'route' => 'curriculum.grade-levels.index'],
        'learning-areas'   => ['label' => 'Learning Areas', 'route' => 'curriculum.learning-areas.index'],
        'pathways'         => ['label' => 'Pathways', 'route' => 'curriculum.pathways.index'],
        'streams'          => ['label' => 'Streams / Classes', 'route' => 'curriculum.streams.index'],
    ];
@endphp

<ul class="nav nav-tabs mb-3">
    @foreach($tabs as $key => $tab)
        <li class="nav-item">
            <a class="nav-link {{ $active === $key ? 'active' : '' }}" href="{{ route($tab['route']) }}">{{ $tab['label'] }}</a>
        </li>
    @endforeach
</ul>
