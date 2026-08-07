<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePaymentRequest;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\MpesaTransaction;
use App\Services\Payments\MpesaStkPushService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function index()
    {
        return view('finance.payments.index');
    }

    private function fullNameExpr(): string
    {
        return "TRIM(REPLACE(CONCAT(users.first_name, ' ', COALESCE(NULLIF(users.middle_name, ''), ''), ' ', users.last_name), '  ', ' '))";
    }

    private function successfulBaseQuery(Request $request)
    {
        $fullNameExpr = $this->fullNameExpr();

        $query = Payment::query()
            ->join('users', 'users.id', '=', 'payments.user_id')
            ->selectRaw("payments.*, {$fullNameExpr} as student_name, userID as student_number");

        if ($method = $request->input('filter_method')) {
            $query->where('payments.method', $method);
        }
        if ($student = trim((string) $request->input('filter_student'))) {
            $query->whereRaw("{$fullNameExpr} LIKE ?", ["%{$student}%"]);
        }
        if ($phone = trim((string) $request->input('filter_phone'))) {
            $query->where('users.phone_number', 'like', "%{$phone}%");
        }
        if ($student_number = trim((string) $request->input('filter_student_number'))) {
            $query->where('users.userID', 'like', "%{$student_number}%");
        }
        if ($reference = trim((string) $request->input('filter_reference'))) {
            $query->where('payments.reference_number', 'like', "%{$reference}%");
        }
        if ($dateFrom = $request->input('filter_date_from')) {
            $query->whereDate('payments.paid_on', '>=', $dateFrom);
        }
        if ($dateTo = $request->input('filter_date_to')) {
            $query->whereDate('payments.paid_on', '<=', $dateTo);
        }

        return $query;
    }

    public function data(Request $request): JsonResponse
    {
        $draw = (int) $request->input('draw', 1);
        $start = (int) $request->input('start', 0);
        $length = max(1, (int) $request->input('length', 25));
        $fullNameExpr = $this->fullNameExpr();

        $query = $this->successfulBaseQuery($request);
        $totalRecords = (clone $query)->count();

        if ($search = trim((string) $request->input('search.value'))) {
            $query->where(function ($q) use ($search, $fullNameExpr) {
                $q->whereRaw("{$fullNameExpr} LIKE ?", ["%{$search}%"])
                    ->orWhere('payments.payment_number', 'like', "%{$search}%")
                    ->orWhere('users.userID', 'like', "%{$search}%")   // fixed
                    ->orWhere('payments.reference_number', 'like', "%{$search}%");
            });
        }

        $filteredRecords = (clone $query)->count();
        $payments = $query->orderByDesc('payments.paid_on')->offset($start)->limit($length)->get();

        $data = $payments->values()->map(fn ($p, $i) => [
            'sn'             => $start + $i + 1,
            'payment_number' => $p->payment_number,
            'student_number' => $p->student_number,
            'student'        => $p->student_name,
            'method'         => $p->method,
            'amount'         => number_format($p->amount, 2),
            'reference'      => $p->reference_number ?: '—',
            'paid_on'        => \Carbon\Carbon::parse($p->paid_on)->format('d M Y'),
        ]);

        return response()->json(['draw' => $draw, 'recordsTotal' => $totalRecords, 'recordsFiltered' => $filteredRecords, 'data' => $data]);
    }

    public function exportSuccessful(Request $request)
    {
        abort_unless($request->user()?->hasPermission('payments.view'), 403);

        $fullNameExpr = $this->fullNameExpr();
        $payments = $this->successfulBaseQuery($request)->orderByDesc('payments.paid_on')->get();

        $filename = 'payments-' . now()->format('Y-m-d-His') . '.csv';

        return response()->streamDownload(function () use ($payments) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Receipt No', 'Student Number', 'Student', 'Method', 'Reference', 'Amount', 'Paid On']);
            foreach ($payments as $p) {
                fputcsv($out, [
                    $p->payment_number,
                    $p->student_number,
                    $p->student_name,
                    ucfirst($p->method),
                    $p->reference_number ?: '—',
                    number_format($p->amount, 2),
                    \Carbon\Carbon::parse($p->paid_on)->format('d M Y'),
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    private function failedBaseQuery(Request $request)
    {
        $fullNameExpr = $this->fullNameExpr();

        $query = MpesaTransaction::query()
            ->join('users', 'users.id', '=', 'mpesa_transactions.user_id')
            ->whereIn('mpesa_transactions.status', ['pending', 'failed', 'cancelled'])
            ->selectRaw("mpesa_transactions.*, {$fullNameExpr} as student_name, users.userID as student_number");

        if ($status = $request->input('filter_status')) {
            $query->where('mpesa_transactions.status', $status);
        }
        if ($student_number = trim((string) $request->input('filter_student_number'))) {
            $query->where('mpesa_transactions.userID', 'like', "%{$student_number}%"); // pick the real column name
        }
        if ($phone = trim((string) $request->input('filter_phone'))) {
            $query->where('mpesa_transactions.phone_number', 'like', "%{$phone}%");
        }
        if ($student_number = trim((string) $request->input('filter_student_number'))) {
            $query->where('mpesa_transactions.studentID', 'like', "%{$student_number}%");
        }
        if ($reference = trim((string) $request->input('filter_reference'))) {
            $query->where('mpesa_transactions.checkout_request_id', 'like', "%{$reference}%");
        }
        if ($dateFrom = $request->input('filter_date_from')) {
            $query->whereDate('mpesa_transactions.created_at', '>=', $dateFrom);
        }
        if ($dateTo = $request->input('filter_date_to')) {
            $query->whereDate('mpesa_transactions.created_at', '<=', $dateTo);
        }

        return $query;
    }

    public function failedData(Request $request): JsonResponse
    {
        $draw = (int) $request->input('draw', 1);
        $start = (int) $request->input('start', 0);
        $length = max(1, (int) $request->input('length', 25));
        $fullNameExpr = $this->fullNameExpr();

        $query = $this->failedBaseQuery($request);
        $totalRecords = (clone $query)->count();

        if ($search = trim((string) $request->input('search.value'))) {
            $query->where(function ($q) use ($search, $fullNameExpr) {
                $q->whereRaw("{$fullNameExpr} LIKE ?", ["%{$search}%"])
                    ->orWhere('mpesa_transactions.phone_number', 'like', "%{$search}%")
                    ->orWhere('mpesa_transactions.userID', 'like', "%{$search}%")
                    ->orWhere('mpesa_transactions.checkout_request_id', 'like', "%{$search}%");
            });
        }

        $filteredRecords = (clone $query)->count();
        $transactions = $query->orderByDesc('mpesa_transactions.created_at')->offset($start)->limit($length)->get();

        $data = $transactions->values()->map(fn ($t, $i) => [
            'sn'             => $start + $i + 1,
            'id'             => $t->id,
            'date'           => $t->created_at->format('d M Y H:i'),
            'student_number' => $t->userID,
            'student'        => $t->student_name,
            'phone'          => $t->phone_number,
            'amount'         => number_format($t->amount, 2),
            'status'         => $t->status,
            'reason'         => $t->result_description ?: '—',
        ]);

        return response()->json(['draw' => $draw, 'recordsTotal' => $totalRecords, 'recordsFiltered' => $filteredRecords, 'data' => $data]);
    }

    public function exportFailed(Request $request)
    {
        abort_unless($request->user()?->hasPermission('payments.view'), 403);

        $transactions = $this->failedBaseQuery($request)->orderByDesc('mpesa_transactions.created_at')->get();
        $filename = 'failed-attempts-' . now()->format('Y-m-d-His') . '.csv';

        return response()->streamDownload(function () use ($transactions) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Date', 'Student', 'Phone', 'Amount', 'Status', 'Reason']);
            foreach ($transactions as $t) {
                fputcsv($out, [
                    $t->created_at->format('d M Y H:i'),
                    $t->student_name,
                    $t->phone_number,
                    number_format($t->amount, 2),
                    ucfirst($t->status),
                    $t->result_description ?: '—',
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function validateTransaction(Request $request, MpesaTransaction $transaction, MpesaStkPushService $mpesa): JsonResponse
    {
        abort_unless($request->user()?->hasPermission('payments.manage'), 403);

        if ($transaction->status !== 'pending' || ! $transaction->checkout_request_id) {
            return response()->json(['status' => $transaction->status]);
        }

        try {
            $result = $mpesa->query($transaction->checkout_request_id);
            $resultCode = $result['ResultCode'] ?? null;

            if ($resultCode !== null) {
                if ((int) $resultCode === 0) {
                    $transaction->update([
                        'status'             => 'success',
                        'result_description' => $result['ResultDesc'] ?? 'Confirmed via query',
                        'paid_at'            => now(),
                    ]);
                } elseif (in_array((int) $resultCode, [1032, 1037])) {
                    $transaction->update([
                        'status'             => (int) $resultCode === 1032 ? 'cancelled' : 'failed',
                        'result_description' => $result['ResultDesc'] ?? null,
                    ]);
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Admin M-Pesa validate failed', ['transaction_id' => $transaction->id, 'error' => $e->getMessage()]);
        }

        return response()->json(['status' => $transaction->fresh()->status]);
    }

    public function create(Request $request)
    {
        $invoice = $request->query('invoice_id') ? Invoice::query()->find($request->query('invoice_id')) : null;

        return view('finance.payments.create', compact('invoice'));
    }

    public function store(StorePaymentRequest $request)
    {
        $validated = $request->validated();
        $invoice = Invoice::query()->findOrFail($validated['invoice_id']);

        DB::transaction(function () use ($validated, $invoice, $request) {
            $count = Payment::query()->count() + 1;

            Payment::query()->create([
                'payment_number'    => 'RCT-' . date('Y') . '-' . str_pad($count, 6, '0', STR_PAD_LEFT),
                'invoice_id'        => $invoice->id,
                'user_id'           => $invoice->user_id,
                'method'            => $validated['method'],
                'amount'            => $validated['amount'],
                'reference_number'  => $validated['reference_number'] ?? null,
                'paid_on'           => $validated['paid_on'],
                'received_by'       => $request->user()->id,
                'notes'             => $validated['notes'] ?? null,
            ]);

            $invoice->recalculate();
        });

        return redirect()->route('finance.invoices.show', $invoice->id)->with('success', 'Payment recorded successfully.');
    }

    public function destroy(Payment $payment): JsonResponse
    {
        abort_unless(request()->user()?->hasPermission('payments.manage'), 403);

        $invoice = $payment->invoice;
        $payment->delete();
        $invoice->recalculate();

        return response()->json(['success' => true, 'message' => 'Payment removed and invoice balance recalculated.']);
    }
}