<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreVoteheadRequest;
use App\Http\Requests\UpdateVoteheadRequest;
use App\Models\Votehead;
use Illuminate\Http\JsonResponse;

class VoteheadController extends Controller
{
    public function index()
    {
        $voteheads = Votehead::query()->orderBy('category')->orderBy('name')->get();

        return view('finance.voteheads.index', compact('voteheads'));
    }

    public function store(StoreVoteheadRequest $request)
    {
        Votehead::query()->create($request->validated());

        return redirect()->route('finance.voteheads.index')->with('success', 'Votehead added successfully.');
    }

    public function update(UpdateVoteheadRequest $request, Votehead $votehead)
    {
        $votehead->update($request->validated());

        return redirect()->route('finance.voteheads.index')->with('success', 'Votehead updated successfully.');
    }

    public function destroy(Votehead $votehead): JsonResponse
    {
        if ($votehead->feeStructureItems()->exists() ?? \App\Models\FeeStructureItem::where('votehead_id', $votehead->id)->exists()) {
            return response()->json(['success' => false, 'message' => 'This votehead is used in one or more fee structures.'], 422);
        }

        $votehead->delete();

        return response()->json(['success' => true, 'message' => 'Votehead deleted successfully.']);
    }
}
