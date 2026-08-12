<?php

namespace App\Http\Controllers;

use App\Models\Exemption;
use App\Models\Invoice;
use App\Models\MpesaTransaction;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\Request;
use Mpdf\Mpdf;

class MyStatementController extends Controller
{
    private function ledger($user): array
    {
        $invoices = Invoice::where('user_id', $user->id)
            ->with('items')
            ->orderBy('academic_year')
            ->orderBy('term')
            ->get();

        $payments = Payment::where('user_id', $user->id)
            ->orderBy('paid_on')
            ->orderBy('created_at')
            ->get()
            ->keyBy('id');

        $exemptions = Exemption::where('user_id', $user->id)
            ->where('status', 'approved')
            ->with('votehead')
            ->get();

        $entries = collect();

        foreach ($invoices as $invoice) {
            $entries->push([
                'date'        => $invoice->created_at->toDateString(),
                'sort_key'    => $invoice->created_at->timestamp . '_inv',
                'type'        => 'invoice',
                'label'       => 'Invoice ' . $invoice->invoice_number,
                'description' => 'Term ' . $invoice->term . ' ' . $invoice->academic_year . ' fees',
                'debit'       => $invoice->total_amount,
                'credit'      => null,
                'invoice'     => $invoice,
            ]);
        }

        foreach (Payment::where('user_id', $user->id)->get() as $payment) {
            $entries->push([
                'date'        => $payment->paid_on->toDateString(),
                'sort_key'    => $payment->paid_on->timestamp . '_pay',
                'type'        => 'payment',
                'label'       => 'Receipt ' . $payment->payment_number,
                'description' => strtoupper($payment->method) . ($payment->reference_number ? ' · ' . $payment->reference_number : ''),
                'debit'       => null,
                'credit'      => $payment->amount,
                'invoice'     => null,
            ]);
        }

        foreach ($exemptions as $exemption) {
            // Fixed exemptions carry their own KES value directly. Percentage
            // exemptions need resolving against whatever they apply to — swap
            // in the real method/relationship once confirmed against your
            // Exemption model; left at 0 rather than guessing a wrong figure.
            $amount = $exemption->type === 'fixed'
                ? (float) $exemption->value
                : (float) ($exemption->resolvedAmount ?? 0);

            $entries->push([
                'date'        => $exemption->created_at->toDateString(),
                'sort_key'    => $exemption->created_at->timestamp . '_exm',
                'type'        => 'exemption',
                'label'       => 'Exemption — ' . ($exemption->votehead->name ?? 'General'),
                'description' => $exemption->reason ?? 'Approved exemption/scholarship',
                'debit'       => null,
                'credit'      => $amount,
                'invoice'     => null,
            ]);
        }

        $sorted = $entries->sortBy('sort_key')->values();

        $balance = 0;
        $ledger = $sorted->map(function ($row) use (&$balance) {
            $balance += ($row['debit'] ?? 0) - ($row['credit'] ?? 0);
            $row['balance'] = $balance;
            return $row;
        });

        $totals = [
            'total_charged'  => $invoices->sum('total_amount'),
            'total_paid'     => Payment::where('user_id', $user->id)->sum('amount'),
            'total_exempted' => $exemptions->sum(fn ($e) => $e->type === 'fixed' ? $e->value : ($e->resolvedAmount ?? 0)),
            'balance'        => $balance,
        ];

        return [$ledger, $totals, $invoices];
    }

    public function index(Request $request)
    {
        $user = $request->user();
        abort_unless($user->hasRole('student'), 403);

        [$ledger, $totals, $invoices] = $this->ledger($user);

        return view('finance.my-statement.index', compact('ledger', 'totals', 'user'));
    }

    public function pdf(Request $request, ?User $viewedUser = null)
    {
        $user = $viewedUser ?? $request->user();
        abort_unless($user->hasRole('student'), 403);

        if ($viewedUser && $viewedUser->id !== $request->user()->id) {
            abort_unless($request->user()->hasPermission('students.statements'), 403);
        }

        [$ledger, $totals] = $this->ledger($user);

        $logoPath = $this->resolveImageBase64(setting('logo_path'));
        $schoolName = setting('school_name', config('app.name'));
        $html = view('finance.my-statement.pdf', compact('ledger', 'totals', 'user', 'logoPath', 'schoolName'))->render();

        $mpdf = new Mpdf([
            'margin_left'   => 10,
            'margin_right'  => 5,
            'margin_top'    => 5,
            'margin_bottom' => 10,
            'margin_header' => 8,
            'margin_footer' => 8,
        ]);

        $mpdf->SetTitle('Student Statement — ' . $user->first_name . ' ' . $user->last_name);
        $mpdf->WriteHTML($html);

        return response($mpdf->Output('', 'S'), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="statement-' . $user->userID . '.pdf"',
        ]);
    }

    private function resolveImageBase64(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        $fullPath = base_path($path); // since path already starts with "Files/..."

        if (! file_exists($fullPath)) {
            \Log::info('Logo file not found', ['path' => $fullPath]);
            return null;
        }

        $mime = mime_content_type($fullPath);
        $data = file_get_contents($fullPath);

        return 'data:' . $mime . ';base64,' . base64_encode($data);
    }
}
