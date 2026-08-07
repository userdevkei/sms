@extends('layouts.app')
@section('title', 'Grading Bands')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <div>
            <h1 class="h4 mb-1">Grading Bands</h1>
            <p class="text-muted mb-0">Letter grades and points assigned to score ranges, used when finalizing subject results.</p>
        </div>
        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#bandModal" onclick="resetBandForm()">
            <i class="bi bi-plus-lg me-1"></i> Add Band
        </button>
    </div>

    <x-results-tabs active="grading-bands" />

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table id="bandsTable" class="table table-hover align-middle w-100 table-striped">
                    <thead>
                    <tr><th>#</th>
                        <th>Range</th><th>Letter Grade</th><th>Points</th><th>Remark</th><th class="text-end">Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($bands as $band)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td data-order="{{ $band->min_score }}">{{ number_format($band->min_score, 2) }} - {{ number_format($band->max_score, 2) }}</td>
                            <td><span class="badge bg-primary-subtle text-primary">{{ $band->letter_grade }}</span></td>
                            <td>{{ $band->points ?? '-' }}</td>
                            <td>{{ $band->remark ?? '-' }}</td>
                            <td class="text-end">
                                <button type="button" class="btn btn-sm btn-outline-secondary"
                                        onclick='editBand(@json($band))'>
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <form method="POST" action="{{ route('results.grading-bands.destroy', $band->id) }}" class="d-inline delete-band-form">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-3">No grading bands defined yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="bandModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" id="bandForm" action="{{ route('results.grading-bands.store') }}">
                @csrf
                <div id="bandMethodField"></div>
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="bandModalTitle">Add Grading Band</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        @if($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                        <div class="row g-2">
                            <div class="col-6">
                                <label class="form-label small text-muted">Min Score</label>
                                <input type="number" step="0.01" min="0" max="100" name="min_score" id="min_score" class="form-control" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label small text-muted">Max Score</label>
                                <input type="number" step="0.01" min="0" max="100" name="max_score" id="max_score" class="form-control" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label small text-muted">Letter Grade</label>
                                <input type="text" maxlength="5" name="letter_grade" id="letter_grade" class="form-control" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label small text-muted">Points</label>
                                <input type="number" min="0" name="points" id="points" class="form-control">
                            </div>
                            <div class="col-12">
                                <label class="form-label small text-muted">Remark</label>
                                <input type="text" maxlength="255" name="remark" id="remark" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
@endpush
@push('scripts')
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $('#bandsTable').DataTable({
            order: [[0, 'asc']],
            pageLength: 25,
            columnDefs: [{ targets: -1, orderable: false }]
        });

        function resetBandForm() {
            document.getElementById('bandForm').action = @json(route('results.grading-bands.store'));
            document.getElementById('bandMethodField').innerHTML = '';
            document.getElementById('bandModalTitle').innerText = 'Add Grading Band';
            ['min_score', 'max_score', 'letter_grade', 'points', 'remark'].forEach(id => document.getElementById(id).value = '');
        }

        function editBand(band) {
            document.getElementById('bandModalTitle').innerText = 'Edit Grading Band';
            document.getElementById('bandForm').action = @json(url('results/grading-bands')) + '/' + band.id;
            document.getElementById('bandMethodField').innerHTML = '@method('PUT')';
            document.getElementById('min_score').value = band.min_score;
            document.getElementById('max_score').value = band.max_score;
            document.getElementById('letter_grade').value = band.letter_grade;
            document.getElementById('points').value = band.points ?? '';
            document.getElementById('remark').value = band.remark ?? '';
            new bootstrap.Modal(document.getElementById('bandModal')).show();
        }

        document.querySelectorAll('.delete-band-form').forEach(form => {
            form.addEventListener('submit', function (e) {
                if (!confirm('Delete this grading band?')) e.preventDefault();
            });
        });

        @if($errors->any())
        new bootstrap.Modal(document.getElementById('bandModal')).show();
        @endif
    </script>
@endpush
