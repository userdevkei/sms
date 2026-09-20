<?php

namespace App\Http\Controllers;

use App\Models\BankTransaction;
use App\Models\Payment;
use App\Models\User;
use App\Services\Banking\BankPaymentReconciliationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class BankReconciliationController extends Controller
{
    /*
     | Works on the existing bank_transactions table — no schema changes.
     | status: 'matched' | anything else counts as 'unmatched'.
     */
    private const STATUS_SQL = "CASE WHEN status = 'matched' THEN 'matched' ELSE 'unmatched' END";

    private const STATUSES = ['unmatched', 'matched'];

    private function authorizeView(Request $request): void
    {
        abort_unless($request->user()?->hasPermission('bank_reconciliation.view'), 403);
    }

    private function authorizeManage(Request $request): void
    {
        abort_unless($request->user()?->hasPermission('bank_reconciliation.manage'), 403);
    }

    private function rowStatus(BankTransaction $tx): string
    {
        return $tx->status === 'matched' ? 'matched' : 'unmatched';
    }

    private function applyStatus(Builder $q, string $status): Builder
    {
        return $status === 'matched'
            ? $q->where('status', 'matched')
            : $q->where('status', '!=', 'matched');
    }

    /* ------------------------------------------------------------------
     | GET /bank-reconciliation
     ------------------------------------------------------------------ */
    public function index(Request $request)
    {
        $this->authorizeView($request);

        return view('finance.bank-reconciliation.index', [
            'banks'  => BankTransaction::query()->select('bank')->distinct()->orderBy('bank')->pluck('bank'),
            'config' => [
                'dataUrl'     => route('finance.bank-reconciliation.data'),
                'studentsUrl' => route('finance.bank-reconciliation.students'),
                'canManage'   => (bool) $request->user()->hasPermission('bank_reconciliation.manage'),
            ],
        ]);
    }

    /* ------------------------------------------------------------------
     | GET /bank-reconciliation/data  — DataTables server-side + card totals
     ------------------------------------------------------------------ */
    public function data(Request $request)
    {
        $this->authorizeView($request);

        $request->validate([
            'status'    => ['nullable', Rule::in(self::STATUSES)],
            'bank'      => ['nullable', 'string', 'max:50'],
            'date_from' => ['nullable', 'date'],
            'date_to'   => ['nullable', 'date'],
        ]);

        $filtered = $this->filteredQuery($request);   // everything EXCEPT the status filter

        // Card totals: per status, honouring bank/date/search but not the status filter itself.
        $summary = (clone $filtered)
            ->selectRaw(self::STATUS_SQL.' as rstatus, COUNT(*) as c, COALESCE(SUM(amount), 0) as total')
            ->groupBy('rstatus')
            ->get()
            ->mapWithKeys(fn ($r) => [$r->rstatus => ['count' => (int) $r->c, 'amount' => (float) $r->total]]);

        $query = (clone $filtered)
            ->when($request->filled('status'), fn (Builder $q) => $this->applyStatus($q, $request->input('status')));

        $recordsTotal    = BankTransaction::count();
        $recordsFiltered = (clone $query)->count();

        // Column index (as declared in the JS) => DB column. Index 0 is the row number (not sortable).
        $orderMap = [
            1 => 'paid_at',
            2 => 'bank',
            3 => 'transaction_ref',
            4 => 'payer_name',
            5 => 'account_reference',
            6 => 'amount',
            7 => 'status',
        ];
        $orderCol = $orderMap[(int) $request->input('order.0.column', 1)] ?? 'paid_at';
        $orderDir = $request->input('order.0.dir') === 'asc' ? 'asc' : 'desc';

        $start  = max((int) $request->input('start', 0), 0);
        $length = min(max((int) $request->input('length', 25), 1), 100);

        $rows = $query->orderBy($orderCol, $orderDir)->offset($start)->limit($length)->get();

        // Batch lookups (no N+1)
        $matchedNames = $this->matchedStudentNames($rows);

        $refs = $rows->filter(fn ($tx) => $this->rowStatus($tx) === 'unmatched')
            ->pluck('account_reference')
            ->filter()
            ->map(fn ($r) => strtoupper(trim((string) $r)))
            ->unique()
            ->values()
            ->all();

        $suggestions = $refs
            ? User::whereHas('enrollments')->whereIn('userID', $refs)->get()
                ->keyBy(fn ($u) => strtoupper($u->userID))
            : collect();

        $data = $rows->values()->map(function ($tx) use ($matchedNames, $suggestions) {
            $status  = $this->rowStatus($tx);
            $paid    = $tx->paid_at ? Carbon::parse($tx->paid_at) : null;
            $suggest = $status === 'unmatched'
                ? $suggestions->get(strtoupper(trim((string) $tx->account_reference)))
                : null;

            return [
                'id'                => $tx->id,
                'date'              => $paid?->format('d M Y') ?? '—',
                'time'              => $paid?->format('H:i'),
                'bank'              => strtoupper((string) $tx->bank),
                'reference'         => $tx->transaction_ref,
                'payer'             => $tx->payer_name,
                'payer_phone'       => $tx->payer_phone,
                'account_reference' => $tx->account_reference,
                'amount'            => (float) $tx->amount,
                'status'            => $status,
                'matched_to'        => $matchedNames[$tx->matched_payment_id] ?? null,
                'matched_via'       => $status === 'matched' ? ($tx->matched_by ? 'Manual' : 'Auto (IPN)') : null,
                'matched_on'        => $status === 'matched' && $tx->matched_at ? Carbon::parse($tx->matched_at)->format('d M Y') : null,
                'suggestion'        => $suggest ? ['id' => $suggest->id, 'text' => "{$suggest->full_name} ({$suggest->userID})"] : null,
                'match_url'         => route('finance.bank-reconciliation.match', $tx->id),
            ];
        });

        return response()->json([
            'draw'            => (int) $request->input('draw'),
            'recordsTotal'    => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data'            => $data,
            'summary'         => $summary,
        ]);
    }

    private function filteredQuery(Request $request): Builder
    {
        $search = trim((string) data_get($request->input('search'), 'value'));

        return BankTransaction::query()
            ->when($request->filled('bank'), fn (Builder $q) => $q->where('bank', $request->input('bank')))
            ->when($request->filled('date_from'), fn (Builder $q) => $q->whereDate('paid_at', '>=', $request->input('date_from')))
            ->when($request->filled('date_to'), fn (Builder $q) => $q->whereDate('paid_at', '<=', $request->input('date_to')))
            ->when($search !== '', function (Builder $q) use ($search) {
                $like = '%'.$search.'%';
                $q->where(function (Builder $w) use ($like) {
                    $w->where('transaction_ref', 'like', $like)
                        ->orWhere('payer_name', 'like', $like)
                        ->orWhere('payer_phone', 'like', $like)
                        ->orWhere('account_reference', 'like', $like);
                });
            });
    }

    /**
     * matched_payment_id => student name (payments.user_id -> users).
     */
    private function matchedStudentNames(Collection $rows): array
    {
        $paymentIds = $rows->pluck('matched_payment_id')->filter()->unique()->all();

        if (! $paymentIds) {
            return [];
        }

        $payments = Payment::whereIn('id', $paymentIds)->get(['id', 'user_id']);
        $users    = User::whereIn('id', $payments->pluck('user_id')->filter()->unique()->all())->get()->keyBy('id');

        return $payments->mapWithKeys(fn ($p) => [$p->id => $users->get($p->user_id)?->full_name])->all();
    }

    /* ------------------------------------------------------------------
     | GET /bank-reconciliation/students?q=  — Select2 source (students only)
     ------------------------------------------------------------------ */
    public function students(Request $request)
    {
        $this->authorizeView($request);

        $q = trim((string) $request->query('q'));

        $users = User::query()
            ->whereHas('enrollments')
            ->when($q !== '', function (Builder $w) use ($q) {
                $like = '%'.$q.'%';
                $w->where(function (Builder $s) use ($like) {
                    $s->where('userID', 'like', $like)
                        ->orWhere('email', 'like', $like)
                        ->orWhereRaw("CONCAT_WS(' ', first_name, middle_name, last_name) LIKE ?", [$like]);
                });
            })
            ->orderBy('first_name')
            ->limit(20)
            ->get();

        return response()->json([
            'results' => $users->map(fn ($u) => [
                'id'   => $u->id,
                'text' => "{$u->full_name} ({$u->userID})",
            ])->values(),
        ]);
    }

    /* ------------------------------------------------------------------
     | POST /bank-reconciliation/{transaction}/match
     ------------------------------------------------------------------ */
    public function match(Request $request, BankTransaction $transaction)
    {
        $this->authorizeManage($request);

        $data = $request->validate([
            'user_id' => ['required', 'string', Rule::exists('users', 'id')->whereNull('deleted_at')],
        ]);

        try {
            DB::transaction(function () use ($transaction, $data, $request) {
                // Lock the row so two people can't match the same transaction at once.
                $tx = BankTransaction::whereKey($transaction->getKey())->lockForUpdate()->firstOrFail();

                if ($tx->status === 'matched') {
                    throw new \DomainException('This transaction has already been matched.');
                }

                $student   = User::findOrFail($data['user_id']);
                $paymentId = $this->postPayment($tx, $student);

                $tx->forceFill([
                    'status'             => 'matched',
                    'matched_payment_id' => $paymentId,
                    'matched_by'         => $request->user()->id,
                    'matched_at'         => now(),
                ])->save();
            });
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Transaction matched.']);
    }

    /**
     * Create the Payment for this bank transaction against the student and return its id
     * (stored in bank_transactions.matched_payment_id). Same service method as the IPN
     * auto-match, so manual and automatic payments are identical. Runs inside the DB
     * transaction in match(): if it throws, nothing is marked as matched.
     */
    private function postPayment(BankTransaction $tx, User $student): string
    {
        $by = auth()->user()?->full_name ?? 'admin';

        return app(BankPaymentReconciliationService::class)
            ->createPayment($tx, $student, "Manually reconciled from {$tx->bank} transactions by {$by}")
            ->id;
    }
}
