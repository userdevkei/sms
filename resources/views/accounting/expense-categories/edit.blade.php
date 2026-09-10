@extends('layouts.app')
@section('title', 'Edit Expense Category')
@section('content')
    @include('accounting.categories._form', [
        'type' => 'expense', 'title' => 'Edit Expense Category',
        'action' => route('accounting.expense-categories.update', $category->id), 'method' => 'PUT', 'category' => $category,
    ])
@endsection
