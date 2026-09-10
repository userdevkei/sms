@extends('layouts.app')
@section('title', 'Add Income Category')
@section('content')
    @include('accounting.categories._form', [
        'type' => 'income', 'title' => 'Add Income Category',
        'action' => route('accounting.income-categories.store'), 'method' => 'POST', 'category' => null,
    ])
@endsection
