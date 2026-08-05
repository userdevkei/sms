<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePaymentRequest;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    public function index()
    {
        return view('finance.payments.index');
    }

    public function data(Request $request): JsonResponse
    {
        $draw = (int) $request->input('draw', 1);
        $start = (int) $request->input('start', 0);
        $length = max(1, (int) $request->input('length', 25));
        $fullNameExpr = "TRIM(REPLACE(CONCAT(users.first_name, ' ', COALESCE(NULLIF(users.middle_name, ''), ''), ' ', users.last_name), '  ', ' '))";

        $query = Payment::query()
            ->join('users', 'users.id', '=', 'payments.user_id')
            ->join('invoices', 'invoices.id', '=', 'payments.invoice_id')
            ->selectRaw("payments.*, invoices.invoice_number, {$fullNameExpr} as student_name");

        if ($method = $request->input('filter_method')) $query->where('payments.method', $method);

        $totalRecords = (clone $query)->count();

        if ($search = trim((string) $request->input('search.value'))) {
            $query->where(function ($q) use ($search, $fullNameExpr) {
                $q->whereRaw("{$fullNameExpr} LIKE ?", ["%{$search}%"])
                    ->orWhere('payments.payment_number', 'like', "%{$search}%")
                    ->orWhere('payments.reference_number', 'like', "%{$search}%")
                    ->orWhere('invoices.invoice_number', 'like', "%{$search}%");
            });
        }

        $filteredRecords = (clone $query)->count();
        $payments = $query->orderByDesc('payments.paid_on')->offset($start)->limit($length)->get();

        $data = $payments->values()->map(fn ($p, $i) => [
            'sn'             => $start + $i + 1,
            'payment_number' => $p->payment_number,
            'invoice_number' => $p->invoice_number,
            'student'        => $p->student_name,
            'method'         => $p->method,
            'amount'         => number_format($p->amount, 2),
            'reference'      => $p->reference_number ?: '\u2014',
            'paid_on'        => \Carbon\Carbon::parse($p->paid_on)->format('d M Y'),
        ]);

        return response()->json(['draw' => $draw, 'recordsTotal' => $totalRecords, 'recordsFiltered' => $filteredRecords, 'data' => $data]);
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
