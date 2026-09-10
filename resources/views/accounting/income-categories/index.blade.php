@extends('layouts.app')
@section('title', 'Income Categories')
@section('content')
    @include('accounting.categories._index', ['type' => 'income', 'title' => 'Income Categories', 'categories' => $categories])
@endsection
