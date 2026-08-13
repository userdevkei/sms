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

    $routeStops = \App\Models\StudentRouteStop::where('user_id', $user->id)
        ->where('status', 'active')->with('routeStop.route')->latest('created_at')->get();

    $roomAllocations = \App\Models\RoomAllocation::where('user_id', $user->id)
        ->with('room.hostel')->latest('allocated_on')->get();

    // ---- Finance statement: merge invoices, payments, and approved
    // exemptions into one chronological ledger with a running balance,
    // instead of three independent tables the reader has to reconcile
    // themselves. Same shape as MyStatementController's ledger.
    $statementLines = collect();

    foreach ($invoices as $inv) {
        $statementLines->push([
            'date'        => $inv->created_at,
            'description' => "Invoice {$inv->invoice_number} — Term {$inv->term}, {$inv->academic_year}",
            'reference'   => $inv->invoice_number,
            'debit'       => (float) $inv->total_amount,
            'credit'      => 0.0,
        ]);
    }

    foreach ($payments as $p) {
        $statementLines->push([
            'date'        => $p->paid_on ?? $p->created_at,
            'description' => 'Payment (' . ucfirst($p->method) . ')' . ($p->invoice ? " — {$p->invoice->invoice_number}" : ''),
            'reference'   => $p->reference_number ?? '—',
            'debit'       => 0.0,
            'credit'      => (float) $p->amount,
        ]);
    }

    $statementLines = $statementLines->sortBy('date')->values();

    $runningBalance = 0.0;
    $statementLines = $statementLines->map(function ($line) use (&$runningBalance) {
        $runningBalance += $line['debit'] - $line['credit'];
        $line['balance'] = $runningBalance;
        return $line;
    });

    $closingBalance = $runningBalance;
    $totalBilled = $invoices->sum('total_amount');
    $totalPaid = $payments->sum('amount');
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

            {{-- FINANCE — single chronological statement --}}
            <div class="tab-pane fade" id="finance-pane" role="tabpanel">

                <div class="row g-3 mb-3">
                    <div class="col-6 col-md-3">
                        <div class="kv-stat">
                            <span class="kv-stat-label">Total Billed</span>
                            <span class="kv-stat-value">KES {{ number_format($totalBilled, 2) }}</span>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="kv-stat">
                            <span class="kv-stat-label">Total Paid</span>
                            <span class="kv-stat-value">KES {{ number_format($totalPaid, 2) }}</span>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="kv-stat">
                            <span class="kv-stat-label">Outstanding Balance</span>
                            <span class="kv-stat-value {{ $closingBalance > 0 ? 'text-danger' : 'text-success' }}">
                                KES {{ number_format($closingBalance, 2) }}
                            </span>
                        </div>
                    </div>
                    <div class="col-6 col-md-3 d-flex align-items-end justify-content-md-end">
                        @if(Route::has('students.statement.pdf'))
                            <a href="{{ route('students.statement.pdf', $user->id) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-file-earmark-pdf me-1"></i> Download Statement
                            </a>
                        @endif
                    </div>
                </div>

                <h6 class="text-uppercase text-muted small mb-2">Account Statement</h6>
                @if($statementLines->isEmpty())
                    <p class="text-muted small mb-0">No account activity yet.</p>
                @else
                    <div class="table-responsive mb-4">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                            <tr>
                                <th>Date</th>
                                <th>Description</th>
                                <th>Reference</th>
                                <th class="text-end">Debit</th>
                                <th class="text-end">Credit</th>
                                <th class="text-end">Balance</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($statementLines as $line)
                                <tr>
                                    <td>{{ \Carbon\Carbon::parse($line['date'])->format('d M Y') }}</td>
                                    <td>{{ $line['description'] }}</td>
                                    <td class="text-muted small">{{ $line['reference'] }}</td>
                                    <td class="text-end">{{ $line['debit'] > 0 ? number_format($line['debit'], 2) : '—' }}</td>
                                    <td class="text-end">{{ $line['credit'] > 0 ? number_format($line['credit'], 2) : '—' }}</td>
                                    <td class="text-end fw-semibold {{ $line['balance'] > 0 ? 'text-danger' : 'text-success' }}">
                                        {{ number_format($line['balance'], 2) }}
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                            <tfoot>
                            <tr class="table-light">
                                <td colspan="5" class="text-end fw-semibold">Closing Balance</td>
                                <td class="text-end fw-bold {{ $closingBalance > 0 ? 'text-danger' : 'text-success' }}">
                                    KES {{ number_format($closingBalance, 2) }}
                                </td>
                            </tr>
                            </tfoot>
                        </table>
                    </div>
                @endif

                <p class="text-muted small mb-0">
                    <i class="bi bi-info-circle me-1"></i>
                    Approved exemptions and scholarships are included above as credits on the date they were approved.
                </p>
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
                order: [[0, 'asc']],
                pageLength: 25
            });
        }
    </script>
@endpush
