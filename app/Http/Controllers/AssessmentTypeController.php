<?php

namespace App\Http\Controllers;

use App\Models\AssessmentType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AssessmentTypeController extends Controller
{
    public function index()
    {
        $assessmentTypes = AssessmentType::query()->orderBy('name')->get();

        return view('results.assessment-types.index', compact('assessmentTypes'));
    }

    public function store(Request $request)
    {
        abort_unless($request->user()?->hasPermission('curriculum.manage'), 403);

        $validated = $request->validate([
            'name'               => ['required', 'string', 'max:100', 'unique:assessment_types,name'],
            'scoring_mode'       => ['required', 'in:score,competency'],
            'default_max_score'  => ['nullable', 'required_if:scoring_mode,score', 'numeric', 'min:1'],
            'description'        => ['nullable', 'string', 'max:500'],
            'status'              => ['required', 'in:active,inactive'],
        ]);

        AssessmentType::query()->create($validated);

        return redirect()->route('results.assessment-types.index')->with('success', 'Assessment type added successfully.');
    }

    public function update(Request $request, AssessmentType $assessmentType)
    {
        abort_unless($request->user()?->hasPermission('curriculum.manage'), 403);

        $validated = $request->validate([
            'name'               => ['required', 'string', 'max:100', Rule::unique('assessment_types', 'name')->ignore($assessmentType->id)],
            'scoring_mode'       => ['required', 'in:score,competency'],
            'default_max_score'  => ['nullable', 'required_if:scoring_mode,score', 'numeric', 'min:1'],
            'description'        => ['nullable', 'string', 'max:500'],
            'status'              => ['required', 'in:active,inactive'],
        ]);

        $assessmentType->update($validated);

        return redirect()->route('results.assessment-types.index')->with('success', 'Assessment type updated successfully.');
    }

    public function destroy(AssessmentType $assessmentType): JsonResponse
    {
        abort_unless(request()->user()?->hasPermission('curriculum.manage'), 403);

        if ($assessmentType->assessments()->exists()) {
            return response()->json(['success' => false, 'message' => 'This type is in use by existing assessments.'], 422);
        }

        $assessmentType->delete();

        return response()->json(['success' => true, 'message' => 'Deleted successfully.']);
    }
}
