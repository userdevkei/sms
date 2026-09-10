@extends('layouts.app')
@section('title', 'Edit Income')
@section('content')
    @include('accounting.transactions._form', [
        'type' => 'income', 'title' => 'Edit Income',
        'action' => route('accounting.income.update', $transaction->id), 'method' => 'PUT', 'transaction' => $transaction,
        'categories' => $categories, 'partyLabel' => 'Received From', 'partyField' => 'received_from',
        'referenceLabel' => 'Receipt No.',
    ])
@endsection
