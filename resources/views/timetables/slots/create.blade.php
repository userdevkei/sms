@extends('layouts.app')
@section('title', 'Add Time Slot')
@section('content')
    @include('timetables.slots._form', [
        'title' => 'Add Time Slot',
        'action' => route('timeslots.store'),
        'method' => 'POST',
        'slot' => null,
    ])
@endsection
