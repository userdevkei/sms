<?php

namespace App\Http\Controllers;

use App\Models\GradeLevel;
use App\Models\Invoice;
use App\Models\User;
use App\Services\Finance\InvoiceGenerationService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InvoiceController extends Controller
{
    public function __construct(private InvoiceGenerationService $generator) {}

    public function index()
    {
        $gradeLevels = GradeLevel::query()->where('status', 'active')->orderBy('sequence')->get();

        return view('finance.invoices.index', compact('gradeLevels'));
    }

    public function data(Request $request): JsonResponse
    {
        $draw = (int) $request->input('draw', 1);
        $start = (int) $request->input('start', 0);
        $length = max(1, (int) $request->input('length', 25));
        $fullNameExpr = "TRIM(REPLACE(CONCAT(users.first_name, ' ', COALESCE(NULLIF(users.middle_name, ''), ''), ' ', users.last_name), '  ', ' '))";

        $query = Invoice::query()
            ->join('users', 'users.id', '=', 'invoices.user_id')
            ->selectRaw("invoices.*, {$fullNameExpr} as student_name, users.userID as student_userid");

        if ($status = $request->input('filter_status')) $query->where('invoices.status', $status);
        if ($gradeLevelId = $request->input('filter_grade_level')) $query->where('invoices.grade_level_id', $gradeLevelId);
        if ($year = $request->input('filter_year')) $query->where('invoices.academic_year', $year);
        if ($term = $request->input('filter_term')) $query->where('invoices.term', $term);

        $totalRecords = (clone $query)->count();

        if ($search = trim((string) $request->input('search.value'))) {
            $query->where(function ($q) use ($search, $fullNameExpr) {
                $q->whereRaw("{$fullNameExpr} LIKE ?", ["%{$search}%"])
                    ->orWhere('invoices.invoice_number', 'like', "%{$search}%")
                    ->orWhere('users.userID', 'like', "%{$search}%");
            });
        }

        $filteredRecords = (clone $query)->count();

        // Map DataTables column index -> actual sortable expression.
        // Column 0 (#) is a row-number column, not a real field, so it isn't sortable.
        $columns = [
            1 => 'invoices.invoice_number',
            2 => $fullNameExpr,
            3 => 'users.userID',
            4 => 'invoices.term', // sorts by term number; add academic_year as tiebreaker below
            5 => 'invoices.total_amount',
            6 => 'invoices.amount_paid',
            7 => 'invoices.balance',
            8 => 'invoices.status',
        ];

        $orderColumnIndex = (int) $request->input('order.0.column', -1);
        $orderDir = strtolower($request->input('order.0.dir', 'desc')) === 'asc' ? 'asc' : 'desc';

        if (isset($columns[$orderColumnIndex])) {
            $query->orderByRaw("{$columns[$orderColumnIndex]} {$orderDir}");
            if ($orderColumnIndex === 4) {
                $query->orderBy('invoices.academic_year', $orderDir);
            }
        } else {
            $query->orderByDesc('invoices.created_at');
        }

        $invoices = $query->offset($start)->limit($length)->get();

        $data = $invoices->values()->map(function ($invoice, $index) use ($start) {
            return [
                'sn'             => $start + $index + 1,
                'invoice_number' => $invoice->invoice_number,
                'student'        => $invoice->student_name,
                'userID'         => $invoice->student_userid ?: '—',
                'term'           => "Term {$invoice->term}, {$invoice->academic_year}",
                'total'          => number_format($invoice->total_amount, 2),
                'paid'           => number_format($invoice->amount_paid, 2),
                'balance'        => number_format($invoice->balance, 2),
                'status'         => $invoice->status,
                'show_url'       => route('finance.invoices.show', $invoice->id),
            ];
        });

        return response()->json(['draw' => $draw, 'recordsTotal' => $totalRecords, 'recordsFiltered' => $filteredRecords, 'data' => $data]);
    }

    public function generateForm()
    {
        $gradeLevels = GradeLevel::query()->where('status', 'active')->orderBy('sequence')->get();
        $students = User::query()->whereHas('roles', fn ($q) => $q->where('slug', 'student'))->orderBy('first_name')->get(['id', 'first_name', 'middle_name', 'last_name', 'userID']);

        return view('finance.invoices.generate', compact('gradeLevels', 'students'));
    }

    /**
     * Combines students from selected grade levels with individually picked
     * students, deduplicated by id.
     */
    private function resolveStudents(array $gradeLevelIds, array $studentIds): Collection
    {
        $query = User::query()->whereHas('roles', fn ($q) => $q->where('slug', 'student'));

        $query->where(function ($q) use ($gradeLevelIds, $studentIds) {
            if (! empty($gradeLevelIds)) {
                $q->orWhereHas('enrollments', fn ($e) => $e->whereIn('grade_level_id', $gradeLevelIds)->where('status', 'active'));
            }
            if (! empty($studentIds)) {
                $q->orWhereIn('id', $studentIds);
            }
        });

        return $query->orderBy('first_name')->get();
    }

    /**
     * Runs the real generateForStudent() inside a transaction, records the
     * resulting invoice (or the fact that it was skipped), then rolls back
     * so nothing is persisted. This guarantees the preview reflects the
     * exact same fee-lookup and duplicate-check logic the real generation
     * uses, instead of a separately maintained copy that could drift out
     * of sync with it.
     */
    private function dryRunForStudent(User $student, string $academicYear, int $term, string $actingUserId): array
    {
        $invoice = null;

        try {
            DB::transaction(function () use ($student, $academicYear, $term, $actingUserId, &$invoice) {
                $invoice = $this->generator->generateForStudent($student, $academicYear, $term, $actingUserId);
                $invoice?->loadMissing(['items', 'gradeLevel']);
                throw new \Illuminate\Database\RecordsNotFoundException(); // force rollback
            });
        } catch (\Illuminate\Database\RecordsNotFoundException) {
            // expected — this is how we discard the write
        }

        return [
            'student'          => $student,
            'grade_level_name' => $invoice?->gradeLevel?->name ?? '—',
            'total'            => $invoice?->total_amount ?? 0,
            'ready'            => (bool) $invoice,
            'reason'           => $invoice ? null : 'No published fee structure, no active enrollment, or already invoiced',
        ];
    }

    public function preview(Request $request)
    {
        abort_unless($request->user()?->hasPermission('invoices.create'), 403);

        $validated = $request->validate([
            'grade_level_ids'   => ['nullable', 'array'],
            'grade_level_ids.*' => ['string', 'exists:grade_levels,id'],
            'student_ids'       => ['nullable', 'array'],
            'student_ids.*'     => ['string', 'exists:users,id'],
            'academic_year'     => ['required', 'string', 'max:9'],
            'term'              => ['required', 'integer', 'in:1,2,3'],
        ]);

        if (empty($validated['grade_level_ids']) && empty($validated['student_ids'])) {
            return back()->withInput()->withErrors(['scope' => 'Select at least one grade level or student.']);
        }

        $students = $this->resolveStudents(
            $validated['grade_level_ids'] ?? [],
            $validated['student_ids'] ?? []
        );

        /*$rows = $students->map(fn (User $student) => $this->dryRunForStudent(
            $student, $validated['academic_year'], (int) $validated['term'], $request->user()->id
        ));*/

        $rows = $students->map(fn (User $student) => array_merge(
            ['student' => $student],
            $this->generator->previewForStudent($student, $validated['academic_year'], (int) $validated['term'])
        ));

        return view('finance.invoices.preview', [
            'rows'         => $rows,
            'academicYear' => $validated['academic_year'],
            'term'         => $validated['term'],
        ]);
    }

    public function storeConfirmed(Request $request)
    {
        abort_unless($request->user()?->hasPermission('invoices.create'), 403);

        $validated = $request->validate([
            'student_ids'   => ['required', 'array', 'min:1'],
            'student_ids.*' => ['string', 'exists:users,id'],
            'academic_year' => ['required', 'string', 'max:9'],
            'term'          => ['required', 'integer', 'in:1,2,3'],
        ]);

        $generated = 0;
        $skipped = 0;

        foreach ($validated['student_ids'] as $userId) {
            $student = User::query()->find($userId);
            $invoice = $student ? $this->generator->generateForStudent(
                $student, $validated['academic_year'], (int) $validated['term'], $request->user()->id
            ) : null;

            $invoice ? $generated++ : $skipped++;
        }

        return redirect()->route('finance.invoices.index')
            ->with('success', "{$generated} invoice(s) generated, {$skipped} skipped.");
    }

    public function show(Invoice $invoice)
    {
        $invoice->load(['items', 'student', 'gradeLevel', 'payments.receivedBy']);

        return view('finance.invoices.show', compact('invoice'));
    }
}
