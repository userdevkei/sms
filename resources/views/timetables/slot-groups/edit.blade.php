@extends('layouts.app')
@section('title', 'Edit Time Slot Group')
@section('content')
    @include('timetables.slot-groups._form', [
        'title' => 'Edit Time Slot Group',
        'action' => route('timeslot-groups.update', $group->id),
        'method' => 'PUT',
        'slot' => $group,
    ])
@endsection
