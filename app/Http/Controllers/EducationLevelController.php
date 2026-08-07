<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEducationLevelRequest;
use App\Http\Requests\UpdateEducationLevelRequest;
use App\Models\EducationLevel;
use Illuminate\Http\JsonResponse;

class EducationLevelController extends Controller
{
    public function index()
    {
        $educationLevels = EducationLevel::query()->withCount('gradeLevels')->orderBy('sequence')->get();

        return view('curriculum.education-levels.index', compact('educationLevels'));
    }

    public function store(StoreEducationLevelRequest $request)
    {
        $level = EducationLevel::query()->create($request->validated());

        return redirect()->route('curriculum.education-levels.index')->with('success', "\"{$level->name}\" was added successfully.");
    }

    public function update(UpdateEducationLevelRequest $request, EducationLevel $educationLevel)
    {
        $educationLevel->update($request->validated());

        return redirect()->route('curriculum.education-levels.index')->with('success', "\"{$educationLevel->name}\" was updated successfully.");
    }

    public function destroy(EducationLevel $educationLevel): JsonResponse
    {
        if ($educationLevel->gradeLevels()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'This level has grade levels defined under it. Remove or reassign those first.',
            ], 422);
        }

        $educationLevel->delete();

        return response()->json(['success' => true, 'message' => 'Education level deleted successfully.']);
    }
}
