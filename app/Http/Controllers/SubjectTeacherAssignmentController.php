<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSubjectTeacherAssignmentRequest;
use App\Models\LearningArea;
use App\Models\Stream;
use App\Models\SubjectTeacherAssignment;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubjectTeacherAssignmentController extends Controller
{
    public function index(Request $request)
    {
        $query = SubjectTeacherAssignment::query()->with(['teacher', 'learningArea', 'stream.gradeLevel']);

        if ($year = $request->query('academic_year')) {
            $query->where('academic_year', $year);
        }

        $assignments = $query->orderBy('academic_year', 'desc')->get();

        return view('results.assignments.index', compact('assignments'));
    }

    public function create()
    {
        $teachers = User::query()->whereHas('roles', fn ($q) => $q->whereIn('slug', ['teacher', 'class_teacher']))
            ->orderBy('first_name')->get(['id', 'first_name', 'middle_name', 'last_name']);
        $learningAreas = LearningArea::query()->where('status', 'active')->orderBy('name')->get();
        $streams = Stream::query()
                    ->with(['gradeLevel', 'pathway', 'classTeacher'])
                    ->join('grade_levels', 'grade_levels.id', '=', 'streams.grade_level_id')
                    ->orderBy('grade_levels.sequence')
                    ->orderBy('streams.name')
                    ->select('streams.*')
                    ->get();

        return view('results.assignments.create', compact('teachers', 'learningAreas', 'streams'));
    }

    public function store(StoreSubjectTeacherAssignmentRequest $request)
    {
        $assignment = SubjectTeacherAssignment::query()->create($request->validated());

        return redirect()->route('results.assignments.index')->with('success', 'Teacher assigned successfully.');
    }

    public function destroy(SubjectTeacherAssignment $assignment): JsonResponse
    {
        abort_unless(request()->user()?->hasPermission('curriculum.manage'), 403);

        if ($assignment->learningArea && \App\Models\Assessment::where('learning_area_id', $assignment->learning_area_id)->where('stream_id', $assignment->stream_id)->exists()) {
            return response()->json(['success' => false, 'message' => 'Assessments already exist for this subject/class combination - removing the assignment would orphan teacher attribution. Deactivate instead.'], 422);
        }

        $assignment->delete();

        return response()->json(['success' => true, 'message' => 'Assignment removed.']);
    }
}
