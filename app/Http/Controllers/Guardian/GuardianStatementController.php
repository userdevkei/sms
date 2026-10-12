<?php

namespace App\Http\Controllers\Guardian;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Finance\StudentLedger;
use Illuminate\Http\Request;
use Mpdf\Mpdf;

class GuardianStatementController extends Controller
{
    public function __construct(private StudentLedger $ledgerService) {}

    public function index(Request $request, User $child)
    {
        [$ledger, $totals] = $this->ledgerService->for($child);

        return view('guardian.statement', compact('child', 'ledger', 'totals'));
    }

    public function pdf(Request $request, User $child)
    {
        [$ledger, $totals] = $this->ledgerService->for($child);

        // The shared PDF template expects $user to be the student.
        $user       = $child;
        $logoPath   = $this->resolveImageBase64(setting('logo_path'));
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

        $mpdf->SetTitle('Student Statement — ' . $child->first_name . ' ' . $child->last_name);
        $mpdf->WriteHTML($html);

        return response($mpdf->Output('', 'S'), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="statement-' . $child->userID . '.pdf"',
        ]);
    }

    private function resolveImageBase64(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        $fullPath = base_path($path);

        if (! file_exists($fullPath)) {
            \Log::info('Logo file not found', ['path' => $fullPath]);

            return null;
        }

        return 'data:' . mime_content_type($fullPath) . ';base64,' . base64_encode(file_get_contents($fullPath));
    }
}
