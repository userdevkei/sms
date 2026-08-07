<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Http\Request;
use Mpdf\Mpdf;

class StudentStatementController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();
        abort_unless($user->hasRole('student'), 403);

        return view('finance.statement.show', $this->statementData($user));
    }

    public function pdf(Request $request)
    {
        $user = $request->user();
        abort_unless($user->hasRole('student'), 403);

        $data = $this->statementData($user);
        $data['schoolName'] = setting('school_name', config('app.name'));
        $data['schoolPhone'] = setting('school_phone');
        $data['logoPath'] = $this->resolveImageBase64(setting('logo_path'));

        $html = view('finance.statement.pdf', $data)->render();

        $mpdf = new Mpdf(['format' => 'A4']);
        $mpdf->WriteHTML($html);

        return $mpdf->Output('statement-' . $user->userID . '.pdf', 'I');
    }

    /**
     * Builds a single chronological ledger: every invoice as a debit line,
     * every payment as a credit line, sorted by date, with a running
     * balance. This is what "statement" means here — not just a list of
     * invoices, but the actual running account like a bank statement.
     */
    private function statementData($user): array
    {
        $invoices = Invoice::where('user_id', $user->id)->get();
        $payments = Payment::where('user_id', $user->id)->with('invoice')->get();

        $lines = collect();

        foreach ($invoices as $inv) {
            $lines->push([
                'date'        => $inv->created_at,
                'description' => "Invoice {$inv->invoice_number} — Term {$inv->term}, {$inv->academic_year}",
                'debit'       => (float) $inv->total_amount,
                'credit'      => 0,
            ]);
        }

        foreach ($payments as $p) {
            $lines->push([
                'date'        => $p->paid_on ?? $p->created_at,
                'description' => "Payment received" . ($p->invoice ? " — {$p->invoice->invoice_number}" : '') . ($p->reference_number ? " (Ref: {$p->reference_number})" : ''),
                'debit'       => 0,
                'credit'      => (float) $p->amount,
            ]);
        }

        $lines = $lines->sortBy('date')->values();

        $running = 0;
        $lines = $lines->map(function ($line) use (&$running) {
            $running += $line['debit'] - $line['credit'];
            $line['balance'] = $running;
            return $line;
        });

        return [
            'user'          => $user,
            'lines'         => $lines,
            'closingBalance'=> $running,
        ];
    }
}
