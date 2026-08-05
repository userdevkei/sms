@php
    $enrollment = $user->currentEnrollment;

    if ($enrollment) {
        $enrollment->load(['gradeLevel', 'stream', 'pathway']);
    }

    $enrollmentIds = $user->enrollments()->pluck('id');

    $publishedResults = \App\Models\TermOverallResult::whereIn('student_enrollment_id', $enrollmentIds)
        ->where('status', 'published')
        ->with('academicTerm')
        ->get()
        ->sortByDesc(fn ($r) => $r->academicTerm->academic_year . str_pad($r->academicTerm->term_number, 2, '0', STR_PAD_LEFT))
        ->values();

    $subjectResults = \App\Models\TermSubjectResult::whereIn('student_enrollment_id', $enrollmentIds)
        ->whereNotNull('finalized_at')
        ->with(['learningArea', 'academicTerm'])
        ->get()
        ->sortByDesc(fn ($r) => $r->academicTerm->academic_year . str_pad($r->academicTerm->term_number, 2, '0', STR_PAD_LEFT))
        ->values();

    // Grading bands loaded once and matched in PHP rather than calling
    // GradingBand::letterFor() per row (N+1 queries) — same lookup logic,
    // just batched. Only applies to numeric average_score results; a
    // competency_level (EE/ME/AE/BE) is a different rating system and is
    // left as-is, not run through score bands.
    $gradingBands = \App\Models\GradingBand::all();
    $letterForScore = function (?float $score) use ($gradingBands) {
        if ($score === null) return null;
        $band = $gradingBands->first(fn ($b) => $score >= $b->min_score && $score <= $b->max_score);
        return $band->letter_grade ?? null;
    };

    $invoices = \App\Models\Invoice::where('user_id', $user->id)
        ->with('gradeLevel')->latest('created_at')->get();

    $payments = \App\Models\Payment::where('user_id', $user->id)
        ->with(['invoice', 'receivedBy'])->latest('paid_on')->get();

    $exemptions = \App\Models\Exemption::where('user_id', $user->id)
        ->with('votehead')->latest('created_at')->get();

    $routeStops = \App\Models\StudentRouteStop::where('user_id', $user->id)
        ->where('status', 'active')->with('routeStop.route')->latest('created_at')->get();

    $roomAllocations = \App\Models\RoomAllocation::where('user_id', $user->id)
        ->with('room.hostel')->latest('allocated_on')->get();
@endphp

@if($enrollment)
    <div class="profile-page">

        <div class="kv-card kv-panel mt-4">
            <div class="kv-panel-head">
                <span class="kv-panel-icon"><i class="bi bi-person-badge"></i></span>
                <h3>Current Standing</h3>
            </div>
            <div class="kv-panel-body">
                <div class="kv-row">
                    <span class="kv-label"><i class="bi bi-mortarboard"></i> Grade Level</span>
                    <span class="kv-value">{{ $enrollment->gradeLevel?->name ?? '—' }}</span>
                </div>
                <div class="kv-row">
                    <span class="kv-label"><i class="bi bi-diagram-3"></i> Stream</span>
                    <span class="kv-value">{{ $enrollment->stream?->name ?? '—' }}</span>
                </div>
                @if($enrollment->pathway)
                    <div class="kv-row">
                        <span class="kv-label"><i class="bi bi-signpost-split"></i> Pathway</span>
                        <span class="kv-value">{{ $enrollment->pathway->name }}</span>
                    </div>
                @endif
                <div class="kv-row">
                    <span class="kv-label"><i class="bi bi-calendar3"></i> Academic Year</span>
                    <span class="kv-value">{{ $enrollment->academic_year }}</span>
                </div>
            </div>
        </div>

        <ul class="nav nav-tabs mt-4" id="profileTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="results-tab" data-bs-toggle="tab" data-bs-target="#results-pane" type="button">
                    <i class="bi bi-journal-text me-1"></i> Results
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="finance-tab" data-bs-toggle="tab" data-bs-target="#finance-pane" type="button">
                    <i class="bi bi-cash-stack me-1"></i> Finance
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="services-tab" data-bs-toggle="tab" data-bs-target="#services-pane" type="button">
                    <i class="bi bi-bus-front me-1"></i> Transport &amp; Accommodation
                </button>
            </li>
        </ul>

        <div class="tab-content border border-top-0 rounded-bottom p-3 bg-white">

            {{-- RESULTS --}}
            <div class="tab-pane fade show active" id="results-pane" role="tabpanel">
                <h6 class="text-uppercase text-muted small mb-2">Published Report Cards</h6>
                @forelse($publishedResults as $result)
                    <div class="kv-row">
                        <span class="kv-label"><i class="bi bi-calendar3"></i> {{ $result->academicTerm->academic_year }} - Term {{ $result->academicTerm->term_number }}</span>
                        <span class="kv-value">
                            Avg {{ number_format($result->average_score, 1) }}
                            <a href="{{ route('results.report-cards.pdf', $result->id) }}" target="_blank" class="kv-link ms-2"><i class="bi bi-file-earmark-pdf"></i> View</a>
                        </span>
                    </div>
                @empty
                    <p class="text-muted small mb-0">No published report cards yet.</p>
                @endforelse

                <h6 class="text-uppercase text-muted small mb-2 mt-4">Subject Results</h6>
                @if($subjectResults->isEmpty())
                    <p class="text-muted small mb-0">No finalized subject results yet.</p>
                @else
                    <div class="table-responsive">
                        <table id="subjectResultsTable" class="table table-sm align-middle w-100 table-striped">
                            <thead>
                            <tr><th>#</th><th>Stage</th><th>Term</th><th>Subject</th><th class="text-end">Score</th><th>Grade</th><th>Remarks</th></tr>
                            </thead>
                            <tbody>
                            @foreach($subjectResults as $r)
                                @php
                                    // Prefer a stored letter_grade (used for competency-rated
                                    // subjects) — otherwise fall back to the grading band
                                    // matched against the numeric average_score.
                                    $displayGrade = $r->letter_grade ?? $letterForScore($r->average_score);
                                @endphp
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $r->enrollment?->gradeLevel?->name }}</td>
                                    <td data-order="{{ $r->academicTerm->academic_year . str_pad($r->academicTerm->term_number, 2, '0', STR_PAD_LEFT) }}">
                                        {{ $r->academicTerm->academic_year }} T{{ $r->academicTerm->term_number }}
                                    </td>
                                    <td>{{ $r->learningArea->name ?? '—' }}</td>
                                    <td class="text-end" data-order="{{ $r->average_score ?? -1 }}">
                                        {{ $r->average_score !== null ? number_format($r->average_score, 1) : ($r->competency_level ?? '—') }}
                                    </td>
                                    <td>{{ $displayGrade ?? '—' }}</td>
                                    <td class="text-muted small">{{ $r->teacher_remarks ?? '—' }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            {{-- FINANCE --}}
            <div class="tab-pane fade" id="finance-pane" role="tabpanel">
                <h6 class="text-uppercase text-muted small mb-2">Invoices</h6>
                @if($invoices->isEmpty())
                    <p class="text-muted small mb-0">No invoices yet.</p>
                @else
                    <div class="table-responsive mb-4">
                        <table class="table table-sm align-middle mb-0">
                            <thead><tr><th>Invoice No.</th><th>Term</th><th class="text-end">Total</th><th class="text-end">Paid</th><th class="text-end">Balance</th><th>Status</th></tr></thead>
                            <tbody>
                            @foreach($invoices as $inv)
                                <tr>
                                    <td>{{ $inv->invoice_number }}</td>
                                    <td>Term {{ $inv->term }}, {{ $inv->academic_year }}</td>
                                    <td class="text-end">{{ number_format($inv->total_amount, 2) }}</td>
                                    <td class="text-end">{{ number_format($inv->amount_paid, 2) }}</td>
                                    <td class="text-end">{{ number_format($inv->balance, 2) }}</td>
                                    <td>
                                        @php $map = ['unpaid' => 'danger', 'partially_paid' => 'warning', 'paid' => 'success', 'cancelled' => 'secondary']; @endphp
                                        <span class="badge bg-{{ $map[$inv->status] ?? 'secondary' }}-subtle text-{{ $map[$inv->status] ?? 'secondary' }} text-capitalize">{{ str_replace('_', ' ', $inv->status) }}</span>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                <h6 class="text-uppercase text-muted small mb-2">Payment History</h6>
                @if($payments->isEmpty())
                    <p class="text-muted small mb-0">No payments recorded yet.</p>
                @else
                    <div class="table-responsive mb-4">
                        <table class="table table-sm align-middle mb-0">
                            <thead><tr><th>Date</th><th>Invoice</th><th>Method</th><th>Reference</th><th class="text-end">Amount</th></tr></thead>
                            <tbody>
                            @foreach($payments as $p)
                                <tr>
                                    <td>{{ $p->paid_on?->format('d M Y') ?? '—' }}</td>
                                    <td>{{ $p->invoice->invoice_number ?? '—' }}</td>
                                    <td class="text-capitalize">{{ $p->method }}</td>
                                    <td>{{ $p->reference_number ?? '—' }}</td>
                                    <td class="text-end">{{ number_format($p->amount, 2) }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                <h6 class="text-uppercase text-muted small mb-2">Exemptions &amp; Scholarships</h6>
                @if($exemptions->isEmpty())
                    <p class="text-muted small mb-0">No exemptions or scholarships recorded.</p>
                @else
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead><tr><th>Term</th><th>Applies To</th><th class="text-end">Value</th><th>Reason</th><th>Status</th></tr></thead>
                            <tbody>
                            @foreach($exemptions as $e)
                                <tr>
                                    <td>Term {{ $e->term }}, {{ $e->academic_year }}</td>
                                    <td>{{ $e->scopeLabel() }}</td>
                                    <td class="text-end">{{ $e->type === 'fixed' ? 'KES ' . number_format($e->value, 2) : number_format($e->value, 1) . '%' }}</td>
                                    <td class="text-muted small">{{ $e->reason ?? '—' }}</td>
                                    <td>
                                        @php $emap = ['pending' => 'secondary', 'approved' => 'success', 'rejected' => 'danger']; @endphp
                                        <span class="badge bg-{{ $emap[$e->status] ?? 'secondary' }}-subtle text-{{ $emap[$e->status] ?? 'secondary' }} text-capitalize">{{ $e->status }}</span>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            {{-- TRANSPORT & ACCOMMODATION --}}
            <div class="tab-pane fade" id="services-pane" role="tabpanel">
                <h6 class="text-uppercase text-muted small mb-2">Route Stop Assignments</h6>
                @if($routeStops->isEmpty())
                    <p class="text-muted small mb-0">No active route stop assignment.</p>
                @else
                    <div class="table-responsive mb-4">
                        <table class="table table-sm align-middle mb-0">
                            <thead><tr><th>Term</th><th>Route</th><th>Stop</th><th class="text-end">Fare</th></tr></thead>
                            <tbody>
                            @foreach($routeStops as $rs)
                                <tr>
                                    <td>Term {{ $rs->term }}, {{ $rs->academic_year }}</td>
                                    <td>{{ $rs->routeStop->route->name ?? '—' }}</td>
                                    <td>{{ $rs->routeStop->name ?? '—' }}</td>
                                    <td class="text-end">{{ number_format($rs->routeStop->fare ?? 0, 2) }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                <h6 class="text-uppercase text-muted small mb-2">Accommodation</h6>
                @if($roomAllocations->isEmpty())
                    <p class="text-muted small mb-0">No accommodation allocation on record.</p>
                @else
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead><tr><th>Academic Year</th><th>Hostel</th><th>Room</th><th>Status</th></tr></thead>
                            <tbody>
                            @foreach($roomAllocations as $ra)
                                <tr>
                                    <td>{{ $ra->academic_year }}</td>
                                    <td>{{ $ra->room->hostel->name ?? '—' }}</td>
                                    <td>{{ $ra->room->full_name ?? '—' }}</td>
                                    <td>
                                        <span class="badge bg-{{ $ra->status === 'active' ? 'success' : 'secondary' }}-subtle text-{{ $ra->status === 'active' ? 'success' : 'secondary' }} text-capitalize">{{ $ra->status }}</span>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

        </div>
    </div>
@endif

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
@endpush
@push('scripts')
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
    <script>
        if (document.getElementById('subjectResultsTable')) {
            $('#subjectResultsTable').DataTable({
                order: [[0, 'desc']],
                pageLength: 10
            });
        }
    </script>
@endpush
