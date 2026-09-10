@extends('layouts.app')
@section('title', 'Add Expense Category')
@section('content')
    @include('accounting.categories._form', [
        'type' => 'income', 'title' => 'Add Expense Category',
        'action' => route('accounting.expense-categories.store'), 'method' => 'POST', 'category' => null,
    ])
@endsection
