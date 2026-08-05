<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreGradeLevelRequest;
use App\Http\Requests\UpdateGradeLevelRequest;
use App\Models\EducationLevel;
use App\Models\GradeLevel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GradeLevelController extends Controller
{
    public function index(Request $request)
    {
        $query = GradeLevel::query()->with('educationLevel')->withCount(['learningAreas', 'streams'])->orderBy('sequence');

        if ($levelId = $request->query('education_level')) {
            $query->where('education_level_id', $levelId);
        }

        $gradeLevels = $query->get();
        $educationLevels = EducationLevel::query()->orderBy('sequence')->get();

        return view('curriculum.grade-levels.index', compact('gradeLevels', 'educationLevels'));
    }

    public function create()
    {
        $educationLevels = EducationLevel::query()->where('status', 'active')->orderBy('sequence')->get();
        $suggestedSequence = (int) (GradeLevel::query()->max('sequence')) + 1;

        return view('curriculum.grade-levels.create', compact('educationLevels', 'suggestedSequence'));
    }

    public function store(StoreGradeLevelRequest $request)
    {
        $grade = GradeLevel::query()->create($request->validated());

        return redirect()->route('curriculum.grade-levels.index')->with('success', "\"{$grade->name}\" was added successfully.");
    }

    public function edit(GradeLevel $gradeLevel)
    {
        $educationLevels = EducationLevel::query()->where('status', 'active')->orderBy('sequence')->get();

        return view('curriculum.grade-levels.edit', compact('gradeLevel', 'educationLevels'));
    }

    public function update(UpdateGradeLevelRequest $request, GradeLevel $gradeLevel)
    {
        $gradeLevel->update($request->validated());

        return redirect()->route('curriculum.grade-levels.index')->with('success', "\"{$gradeLevel->name}\" was updated successfully.");
    }

    public function destroy(GradeLevel $gradeLevel): JsonResponse
    {
        if ($gradeLevel->streams()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'This grade level has streams/classes defined under it. Remove those first.',
            ], 422);
        }

        $gradeLevel->learningAreas()->detach();
        $gradeLevel->delete();

        return response()->json(['success' => true, 'message' => 'Grade level deleted successfully.']);
    }
}
