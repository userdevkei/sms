<?php

namespace App\Http\Controllers;

use App\Models\MpesaTransaction;
use App\Models\Payment;
use Illuminate\Http\Request;

class AdminPaymentController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()?->hasPermission('payments.view'), 403);

        $successfulPayments = Payment::with(['student', 'invoice', 'receivedBy'])->latest('paid_on')->get();
        $failedAttempts = MpesaTransaction::with('student')->where('status', '!=', 'success')->latest('created_at')->get();

        return view('finance.payments.index', compact('successfulPayments', 'failedAttempts'));
    }
}
