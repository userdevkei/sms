<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLearningAreaRequest;
use App\Http\Requests\UpdateLearningAreaRequest;
use App\Models\EducationLevel;
use App\Models\LearningArea;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LearningAreaController extends Controller
{
    public function index(Request $request)
    {
        $query = LearningArea::query()->withCount('gradeLevels')->orderBy('name');

        if ($gradeLevelId = $request->query('grade_level')) {
            $query->whereHas('gradeLevels', fn ($q) => $q->where('grade_levels.id', $gradeLevelId));
        }

        $learningAreas = $query->get();

        return view('curriculum.learning-areas.index', compact('learningAreas'));
    }

    public function create()
    {
        $educationLevels = EducationLevel::query()->with('gradeLevels')->orderBy('sequence')->get();

        return view('curriculum.learning-areas.create', compact('educationLevels'));
    }

    public function store(StoreLearningAreaRequest $request)
    {
        $validated = $request->validated();
        $gradeLevelIds = $validated['grade_levels'] ?? [];
        unset($validated['grade_levels']);
        $validated['is_compulsory'] = $request->boolean('is_compulsory');

        $learningArea = LearningArea::query()->create($validated);
        $learningArea->gradeLevels()->sync($gradeLevelIds);

        return redirect()->route('curriculum.learning-areas.index')->with('success', "\"{$learningArea->name}\" was added successfully.");
    }

    public function edit(LearningArea $learningArea)
    {
        $educationLevels = EducationLevel::query()->with('gradeLevels')->orderBy('sequence')->get();
        $selectedGradeLevelIds = $learningArea->gradeLevels()->pluck('grade_levels.id')->all();

        return view('curriculum.learning-areas.edit', compact('learningArea', 'educationLevels', 'selectedGradeLevelIds'));
    }

    public function update(UpdateLearningAreaRequest $request, LearningArea $learningArea)
    {
        $validated = $request->validated();
        $gradeLevelIds = $validated['grade_levels'] ?? [];
        unset($validated['grade_levels']);
        $validated['is_compulsory'] = $request->boolean('is_compulsory');

        $learningArea->update($validated);
        $learningArea->gradeLevels()->sync($gradeLevelIds);

        return redirect()->route('curriculum.learning-areas.index')->with('success', "\"{$learningArea->name}\" was updated successfully.");
    }

    public function destroy(LearningArea $learningArea): JsonResponse
    {
        $learningArea->gradeLevels()->detach();
        $learningArea->pathways()->detach();
        $learningArea->delete();

        return response()->json(['success' => true, 'message' => 'Learning area deleted successfully.']);
    }
}
