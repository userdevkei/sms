@extends('layouts.app')
@section('title', 'Preview Invoices')

@section('content')
    <div class="mb-3">
        <h1 class="h4 mb-1">Preview Invoices</h1>
        <p class="text-muted mb-0">Term {{ $term }}, {{ $academicYear }} — review before generating. Uncheck any student you want to skip.</p>
    </div>

    <form method="POST" action="{{ route('finance.invoices.store-confirmed') }}">
        @csrf
        <input type="hidden" name="academic_year" value="{{ $academicYear }}">
        <input type="hidden" name="term" value="{{ $term }}">

        <div class="card border-0 shadow-sm mb-3">
            <div class="table-responsive">
                <table class="table align-middle mb-0 table-striped">
                    <thead>
                    <tr>
                        <th style="width:40px"><input type="checkbox" id="checkAll" checked></th>
                        <th>Student</th>
                        <th>Grade</th>
                        <th class="text-end">Total (KES)</th>
                        <th>Status</th>
                    </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $row)
                            <tr class="{{ $row['ready'] ? '' : 'text-muted' }}">
                                <td>
                                    <input type="checkbox" name="student_ids[]" value="{{ $row['student']->id }}"
                                        {{ $row['ready'] ? 'checked' : 'disabled' }}>
                                </td>
                                <td>{{ $row['student']->full_name }} <span class="text-muted small">({{ $row['student']->userID ?: '—' }})</span></td>
                                <td>{{ $row['grade_level_name'] }}</td>
                                <td class="text-end">{{ number_format($row['total'], 2) }}</td>
                                <td>
                                    @if($row['ready'])
                                        <span class="badge bg-success">Ready</span>
                                    @else
                                        <span class="badge bg-danger">{{ $row['reason'] }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-4">No students match your selection.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="d-flex justify-content-between">
            <a href="{{ route('finance.invoices.generate-form') }}" class="btn btn-sm btn-outline-secondary">Back</a>
            <button type="submit" class="btn btn-sm btn-primary px-4" @if($rows->isEmpty()) disabled @endif>
                <i class="bi bi-check-lg me-1"></i> Confirm & Generate
            </button>
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        document.getElementById('checkAll')?.addEventListener('change', function () {
            document.querySelectorAll('input[name="student_ids[]"]:not(:disabled)').forEach(cb => cb.checked = this.checked);
        });
    </script>
@endpush
