@extends('layouts.app')
@section('title', 'Expense Categories')
@section('content')
    @include('accounting.categories._index', ['type' => 'expense', 'title' => 'Expense Categories', 'categories' => $categories])
@endsection
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
