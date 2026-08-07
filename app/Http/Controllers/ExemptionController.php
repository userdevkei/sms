<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreExemptionRequest;
use App\Models\Exemption;
use App\Models\User;
use App\Models\Votehead;
use Illuminate\Http\Request;

class ExemptionController extends Controller
{
    public function index()
    {
        $pending = Exemption::query()->where('status', 'pending')->with(['student', 'votehead', 'requestedBy'])->latest()->get();
        $resolved = Exemption::query()->where('status', '!=', 'pending')->with(['student', 'votehead', 'approvedBy'])->latest('approved_at')->limit(50)->get();

        return view('finance.exemptions.index', compact('pending', 'resolved'));
    }

    public function create()
    {
        $students = User::query()->whereHas('roles', fn ($q) => $q->where('slug', 'student'))->orderBy('first_name')->get(['id', 'first_name', 'middle_name', 'last_name', 'userID']);
        $voteheads = Votehead::query()->where('status', 'active')->orderBy('name')->get();

        return view('finance.exemptions.create', compact('students', 'voteheads'));
    }

    public function store(StoreExemptionRequest $request)
    {
        $validated = $request->validated();
        $validated['status'] = 'pending';
        $validated['requested_by'] = $request->user()->id;

        Exemption::query()->create($validated);

        return redirect()->route('finance.exemptions.index')->with('success', 'Exemption request submitted for approval.');
    }

    public function approve(Request $request, Exemption $exemption)
    {
        abort_unless($request->user()?->hasPermission('exemptions.approve'), 403);
        abort_unless($exemption->status === 'pending', 422, 'Already decided.');

        $exemption->update(['status' => 'approved', 'approved_by' => $request->user()->id, 'approved_at' => now()]);

        return back()->with('success', "Exemption approved for {$exemption->student->full_name}. Regenerate their invoice to apply it.");
    }

    public function reject(Request $request, Exemption $exemption)
    {
        abort_unless($request->user()?->hasPermission('exemptions.approve'), 403);
        abort_unless($exemption->status === 'pending', 422, 'Already decided.');

        $exemption->update(['status' => 'rejected', 'approved_by' => $request->user()->id, 'approved_at' => now()]);

        return back()->with('success', 'Exemption rejected.');
    }
}
