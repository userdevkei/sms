<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePathwayRequest;
use App\Http\Requests\UpdatePathwayRequest;
use App\Models\LearningArea;
use App\Models\Pathway;
use Illuminate\Http\JsonResponse;

class PathwayController extends Controller
{
    public function index()
    {
        $pathways = Pathway::query()->withCount(['learningAreas', 'streams'])->orderBy('name')->get();

        return view('curriculum.pathways.index', compact('pathways'));
    }

    public function create()
    {
        $learningAreas = LearningArea::query()->where('status', 'active')->orderBy('name')->get();

        return view('curriculum.pathways.create', compact('learningAreas'));
    }

    public function store(StorePathwayRequest $request)
    {
        $validated = $request->validated();
        $learningAreaIds = $validated['learning_areas'] ?? [];
        unset($validated['learning_areas']);

        $pathway = Pathway::query()->create($validated);
        $pathway->learningAreas()->sync($learningAreaIds);

        return redirect()->route('curriculum.pathways.index')->with('success', "\"{$pathway->name}\" was added successfully.");
    }

    public function edit(Pathway $pathway)
    {
        $learningAreas = LearningArea::query()->where('status', 'active')->orderBy('name')->get();
        $selectedLearningAreaIds = $pathway->learningAreas()->pluck('learning_areas.id')->all();

        return view('curriculum.pathways.edit', compact('pathway', 'learningAreas', 'selectedLearningAreaIds'));
    }

    public function update(UpdatePathwayRequest $request, Pathway $pathway)
    {
        $validated = $request->validated();
        $learningAreaIds = $validated['learning_areas'] ?? [];
        unset($validated['learning_areas']);

        $pathway->update($validated);
        $pathway->learningAreas()->sync($learningAreaIds);

        return redirect()->route('curriculum.pathways.index')->with('success', "\"{$pathway->name}\" was updated successfully.");
    }

    public function destroy(Pathway $pathway): JsonResponse
    {
        if ($pathway->streams()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'This pathway has streams/classes assigned to it. Reassign or remove those first.',
            ], 422);
        }

        $pathway->learningAreas()->detach();
        $pathway->delete();

        return response()->json(['success' => true, 'message' => 'Pathway deleted successfully.']);
    }
}
