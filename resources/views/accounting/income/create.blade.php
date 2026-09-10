@extends('layouts.app')
@section('title', 'Record Income')
@section('content')
    @include('accounting.transactions._form', [
        'type' => 'income', 'title' => 'Record Income',
        'action' => route('accounting.income.store'), 'method' => 'POST', 'transaction' => null,
        'categories' => $categories, 'partyLabel' => 'Received From', 'partyField' => 'received_from',
        'referenceLabel' => 'Receipt No.',
    ])
@endsection
