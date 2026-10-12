<?php

namespace App\Http\Controllers\Guardian;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use App\Services\Guardian\StudentSnapshot;
use App\Support\GuardianNav;
use Illuminate\Http\Request;

class GuardianDashboardController extends Controller
{
    public function __construct(private StudentSnapshot $snapshot, private GuardianNav $nav) {}

    public function index(Request $request)
    {
        $guardian = $request->user();
        $students = $guardian->activeStudents()->orderBy('users.first_name')->get();
        $snap     = $this->snapshot->for($students->pluck('id'));

        $totals = [
            'students'    => $students->count(),
            'outstanding' => collect($snap)->sum(fn ($s) => max($s['balance'], 0)),
            'credit'      => collect($snap)->sum(fn ($s) => abs(min($s['balance'], 0))),
            'next_due'    => collect($snap)->pluck('next_due')->filter()->sortBy(fn ($d) => $d->timestamp)->first(),
        ];

        $recentPayments = Payment::whereIn('user_id', $students->pluck('id'))
            ->orderByDesc(StudentSnapshot::PAYMENT_DATE)->limit(6)->get();

        return view('guardian.dashboard', compact('guardian', 'students', 'snap', 'totals', 'recentPayments'));
    }

    public function student(Request $request, User $child)
    {
        $snap = $this->snapshot->for([$child->id])[$child->id];

        $openInvoices = Invoice::where('user_id', $child->id)->where('balance', '>', 0)
            ->orderBy(StudentSnapshot::INVOICE_DUE)->limit(5)->get();

        $recentPayments = Payment::where('user_id', $child->id)
            ->orderByDesc(StudentSnapshot::PAYMENT_DATE)->limit(5)->get();

        $quickLinks = $this->nav->quickLinks($child);

        return view('guardian.student', compact('child', 'snap', 'openInvoices', 'recentPayments', 'quickLinks'));
    }
}
