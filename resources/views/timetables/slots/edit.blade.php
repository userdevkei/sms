@extends('layouts.app')
@section('title', 'Edit Time Slot')
@section('content')
    @include('timetables.slots._form', [
        'title' => 'Edit Time Slot',
        'action' => route('timeslots.update', $timeslot->id),
        'method' => 'PUT',
        'slot' => $timeslot,
    ])
@endsection
