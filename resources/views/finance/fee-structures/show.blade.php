@extends('layouts.app')
@section('title', 'Fee Structure')

@section('content')
    <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
            <h1 class="h4 mb-1">{{ $feeStructure->gradeLevel->name }} Fees Structure</h1>
            <p class="text-muted mb-0">Version {{ $feeStructure->version }} - Created by {{ $feeStructure->creator->full_name }} on {{ $feeStructure->created_at->format('d M Y') }}</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('finance.fee-structures.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Back</a>
            @can('fee_structures.approve')
                @if($feeStructure->status === 'draft')
                    <form method="POST" action="{{ route('finance.fee-structures.publish', $feeStructure->id) }}" onsubmit="return confirm('Publish this version? Any currently-published version for this grade/year/term will be archived.')">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-success"><i class="bi bi-check2-circle me-1"></i> Publish</button>
                    </form>
                @endif
            @endcan
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead><tr><th>Votehead</th><th>Category</th><th class="text-end">Amount</th></tr></thead>
                    <tbody>
                    @foreach($feeStructure->items as $item)
                        <tr>
                            <td>{{ $item->votehead->name }}</td>
                            <td><span class="badge bg-secondary-subtle text-secondary text-capitalize">{{ $item->votehead->category }}</span></td>
                            <td class="text-end">KES {{ number_format($item->amount, 2) }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                    <tfoot>
                    <tr class="fw-semibold"><td colspan="2">Total</td><td class="text-end">KES {{ number_format($feeStructure->items->sum('amount'), 2) }}</td></tr>
                    </tfoot>
                </table>
            </div>
            @if($feeStructure->notes)
                <p class="text-muted small mt-3 mb-0"><i class="bi bi-sticky"></i> {{ $feeStructure->notes }}</p>
            @endif
        </div>
    </div>
@endsection
