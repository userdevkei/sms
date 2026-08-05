<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStreamRequest;
use App\Http\Requests\UpdateStreamRequest;
use App\Models\GradeLevel;
use App\Models\Pathway;
use App\Models\Stream;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StreamController extends Controller
{
    public function index(Request $request)
    {
        $query = Stream::query()
            ->with(['gradeLevel', 'pathway', 'classTeacher'])
            ->join('grade_levels', 'grade_levels.id', '=', 'streams.grade_level_id')
            ->orderBy('grade_levels.sequence')
            ->orderBy('streams.name')
            ->select('streams.*'); // avoid column collisions from the join (both tables may have 'id', 'created_at', etc.)

        if ($gradeLevelId = $request->query('grade_level')) {
            $query->where('streams.grade_level_id', $gradeLevelId);
        }

        $streams = $query->get();
        $gradeLevels = GradeLevel::query()->orderBy('sequence')->get();

        return view('curriculum.streams.index', compact('streams', 'gradeLevels'));
    }

    public function create()
    {
        $gradeLevels = GradeLevel::query()->where('status', 'active')->with('educationLevel')->orderBy('sequence')->get();
        $pathways = Pathway::query()->where('status', 'active')->orderBy('name')->get();
        $classTeacherCandidates = User::query()
            ->whereHas('roles', fn ($q) => $q->whereIn('slug', ['class_teacher', 'teacher']))
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'middle_name', 'last_name']);

        return view('curriculum.streams.create', compact('gradeLevels', 'pathways', 'classTeacherCandidates'));
    }

    public function store(StoreStreamRequest $request)
    {
        $stream = Stream::query()->create($request->validated());

        return redirect()->route('curriculum.streams.index')->with('success', "\"{$stream->full_name}\" was added successfully.");
    }

    public function edit(Stream $stream)
    {
        $gradeLevels = GradeLevel::query()->where('status', 'active')->with('educationLevel')->orderBy('sequence')->get();
        $pathways = Pathway::query()->where('status', 'active')->orderBy('name')->get();
        $classTeacherCandidates = User::query()
            ->whereHas('roles', fn ($q) => $q->whereIn('slug', ['class_teacher', 'teacher']))
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'middle_name', 'last_name']);

        return view('curriculum.streams.edit', compact('stream', 'gradeLevels', 'pathways', 'classTeacherCandidates'));
    }

    public function update(UpdateStreamRequest $request, Stream $stream)
    {
        $stream->update($request->validated());

        return redirect()->route('curriculum.streams.index')->with('success', "\"{$stream->full_name}\" was updated successfully.");
    }

    public function destroy(Stream $stream): JsonResponse
    {
        $stream->delete();

        return response()->json(['success' => true, 'message' => 'Stream deleted successfully.']);
    }
}
