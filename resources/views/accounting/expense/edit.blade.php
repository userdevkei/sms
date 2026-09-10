@extends('layouts.app')
@section('title', 'Edit Expense')
@section('content')
    @include('accounting.transactions._form', [
        'type' => 'expense', 'title' => 'Edit Expense',
        'action' => route('accounting.expense.update', $transaction->id), 'method' => 'PUT', 'transaction' => $transaction,
        'categories' => $categories, 'partyLabel' => 'Vendor', 'partyField' => 'vendor',
        'referenceLabel' => 'Receipt No.',
    ])
@endsection
