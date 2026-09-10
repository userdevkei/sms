@extends('layouts.app')
@section('title', 'Edit Income Category')
@section('content')
    @include('accounting.categories._form', [
        'type' => 'income', 'title' => 'Edit Income Category',
        'action' => route('accounting.income-categories.update', $category->id), 'method' => 'PUT', 'category' => $category,
    ])
@endsection
