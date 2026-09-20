@extends('layouts.app')
@section('title', 'Add Time Slot Group')
@section('content')
    @include('timetables.slot-groups._form', [
        'title' => 'Add Time Slot Group',
        'action' => route('timeslot-groups.store'),
        'method' => 'POST',
        'group' => null,
    ])
@endsection
