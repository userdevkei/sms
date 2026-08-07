@extends('layouts.app')
@section('title', 'Progression Exceptions')

@section('content')
    <div class="mb-3">
        <h1 class="h4 mb-1">Progression Exceptions</h1>
        <p class="text-muted mb-0">Special cases awaiting review, and recently resolved requests.</p>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <h6 class="text-uppercase text-muted small mb-3">Pending Approval ({{ $pending->count() }})</h6>
            @forelse($pending as $exception)
                <div class="border rounded p-3 mb-2">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                        <div>
                            <div class="fw-semibold">{{ $exception->student->full_name }}
                                <span class="badge bg-warning-subtle text-warning ms-1">{{ $exception->typeLabel() }}</span>
                            </div>
                            <div class="text-muted small mb-2">
                                {{ $exception->enrollment->gradeLevel->name }} \u{00B7} Requested by {{ $exception->requestedBy->full_name }} on {{ $exception->created_at->format('d M Y') }}
                                @if($exception->type === 'repeat') \u{00B7} Repeat year: {{ $exception->new_academic_year }} @endif
                            </div>
                            <p class="mb-0">{{ $exception->reason }}</p>
                        </div>
                        @can('progression.approve')
                            <div class="d-flex gap-2">
                                <form method="POST" action="{{ route('curriculum.progression.exceptions.approve', $exception->id) }}"
                                      onsubmit="return confirm('Approve this exception? It will be applied immediately.')">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-success"><i class="bi bi-check-lg"></i> Approve</button>
                                </form>
                                <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#rejectModal{{ $exception->id }}">
                                    <i class="bi bi-x-lg"></i> Reject
                                </button>
                            </div>
                        @endcan
                    </div>
                </div>

                @can('progression.approve')
                    <div class="modal fade" id="rejectModal{{ $exception->id }}" tabindex="-1">
                        <div class="modal-dialog">
                            <form method="POST" action="{{ route('curriculum.progression.exceptions.reject', $exception->id) }}">
                                @csrf
                                <div class="modal-content">
                                    <div class="modal-header"><h5 class="modal-title">Reject Exception</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                                    <div class="modal-body">
                                        <label class="form-label">Notes (optional)</label>
                                        <textarea name="review_notes" rows="3" class="form-control" placeholder="Why is this being rejected?"></textarea>
                                    </div>
                                    <div class="modal-footer"><button type="submit" class="btn btn-sm btn-danger">Confirm Rejection</button></div>
                                </div>
                            </form>
                        </div>
                    </div>
                @endcan
            @empty
                <p class="text-muted small fst-italic mb-0">No pending exceptions.</p>
            @endforelse
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <h6 class="text-uppercase text-muted small mb-3">Recently Resolved</h6>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead><tr><th>Student</th><th>Type</th><th>Decision</th><th>Reviewed By</th><th>Date</th></tr></thead>
                    <tbody>
                    @forelse($resolved as $exception)
                        <tr>
                            <td>{{ $exception->student->full_name }}</td>
                            <td>{{ $exception->typeLabel() }}</td>
                            <td>
                                <span class="badge bg-{{ $exception->status === 'approved' ? 'success' : 'danger' }}-subtle text-{{ $exception->status === 'approved' ? 'success' : 'danger' }} text-capitalize">
                                    {{ $exception->status }}
                                </span>
                            </td>
                            <td>{{ $exception->reviewedBy?->full_name ?? '-' }}</td>
                            <td>{{ $exception->reviewed_at?->format('d M Y') ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-3">No resolved exceptions yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
