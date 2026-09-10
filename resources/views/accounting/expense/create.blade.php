@extends('layouts.app')
@section('title', 'Record Expense')
@section('content')
    @include('accounting.transactions._form', [
        'type' => 'expense', 'title' => 'Record Expense',
        'action' => route('accounting.expense.store'), 'method' => 'POST', 'transaction' => null,
        'categories' => $categories, 'partyLabel' => 'Received From', 'partyField' => 'vendor',
        'referenceLabel' => 'Invoice No.',
    ])
@endsection
