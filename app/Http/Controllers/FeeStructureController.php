<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFeeStructureRequest;
use App\Models\FeeStructure;
use App\Models\GradeLevel;
use App\Models\Votehead;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FeeStructureController extends Controller
{
    public function index(Request $request)
    {
        $query = FeeStructure::query()->with(['gradeLevel', 'items']);

        if ($gradeLevelId = $request->query('grade_level')) {
            $query->where('grade_level_id', $gradeLevelId);
        }

        $feeStructures = $query->get();
        $gradeLevels = GradeLevel::query()->orderBy('sequence')->get();

        return view('finance.fee-structures.index', compact('feeStructures', 'gradeLevels'));
    }

    public function create()
    {
        $gradeLevels = GradeLevel::query()->where('status', 'active')->orderBy('sequence')->get();
        $voteheads = Votehead::query()->where('status', 'active')->orderBy('category')->orderBy('name')->get();

        return view('finance.fee-structures.create', compact('gradeLevels', 'voteheads'));
    }

    /**
     * Creates one FeeStructure draft per selected grade level, each with its
     * own version number and a copy of the same fee items. This lets a user
     * apply an identical fee structure across several grades in one submission
     * without pretending the grades share a single structure record.
     */
    public function store(StoreFeeStructureRequest $request)
    {
        $validated = $request->validated();

        $structures = DB::transaction(function () use ($validated, $request) {
            $created = [];

            foreach ($validated['grade_level_ids'] as $gradeLevelId) {
                $version = FeeStructure::query()
                        ->where('grade_level_id', $gradeLevelId)
                        ->max('version') + 1;

                $structure = FeeStructure::query()->create([
                    'grade_level_id' => $gradeLevelId,
                    'version'        => $version,
                    'status'         => 'draft',
                    'notes'          => $validated['notes'] ?? null,
                    'created_by'     => $request->user()->id,
                ]);

                foreach ($validated['items'] as $item) {
                    $structure->items()->create($item);
                }

                $created[] = $structure;
            }

            return $created;
        });

        if (count($structures) === 1) {
            return redirect()->route('finance.fee-structures.show', $structures[0]->id)
                ->with('success', "Fee structure v{$structures[0]->version} created as a draft.");
        }

        return redirect()->route('finance.fee-structures.index')
            ->with('success', count($structures) . ' fee structure drafts created — one for each selected grade level.');
    }

    public function show(FeeStructure $feeStructure)
    {
        $feeStructure->load(['items.votehead', 'gradeLevel', 'creator', 'publisher']);

        return view('finance.fee-structures.show', compact('feeStructure'));
    }

    /**
     * Publishing archives any other published version for the same grade
     * level — "one published version at a time" is now scoped purely to
     * grade level, since fee structures are no longer tied to a term/year.
     */
    public function publish(Request $request, FeeStructure $feeStructure): \Illuminate\Http\RedirectResponse
    {
        abort_unless($request->user()?->hasPermission('fee_structures.approve'), 403);
        abort_unless($feeStructure->status === 'draft', 422, 'Only draft structures can be published.');

        DB::transaction(function () use ($feeStructure, $request) {
            FeeStructure::query()
                ->where('grade_level_id', $feeStructure->grade_level_id)
                ->where('status', 'published')
                ->update(['status' => 'archived']);

            $feeStructure->update([
                'status'       => 'published',
                'published_by' => $request->user()->id,
                'published_at' => now(),
            ]);
        });

        return back()->with('success', "Version {$feeStructure->version} is now published and active.");
    }

    public function destroy(FeeStructure $feeStructure): JsonResponse
    {
        if ($feeStructure->status === 'published') {
            return response()->json(['success' => false, 'message' => 'Cannot delete a published fee structure. Archive it instead by publishing a new version.'], 422);
        }

        $feeStructure->items()->delete();
        $feeStructure->delete();

        return response()->json(['success' => true, 'message' => 'Draft fee structure deleted.']);
    }
}
