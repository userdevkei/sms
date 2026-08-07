<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAssessmentRequest;
use App\Models\AcademicTerm;
use App\Models\Assessment;
use App\Models\AssessmentType;
use App\Models\GradeLevel;
use App\Models\LearningArea;
use App\Models\Stream;
use App\Models\StudentEnrollment;
use App\Models\SubjectTeacherAssignment;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AssessmentController extends Controller
{
    /**
     * Streams/subjects a given teacher is allowed to touch, based on
     * subject_teacher_assignments — the single scoping mechanism that keeps
     * a teacher from seeing or creating assessments outside their own subjects.
     * Users with curriculum.manage or results.approve bypass this (full visibility).
     */
    private function teacherScopedAssignments($user)
    {
        if ($user->hasPermission('curriculum.manage') || $user->hasPermission('results.approve')) {
            return null; // null = no restriction
        }

        return SubjectTeacherAssignment::query()
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->get(['learning_area_id', 'stream_id']);
    }

//    public function index(Request $request)
//    {
//        /*DB::transaction(function () {
//
//            $students = User::join('role_users', 'role_users.user_id', '=', 'users.id')
//                ->join('roles', 'role_users.role_id', '=', 'roles.id')
//                ->where('roles.slug', 'student')
//                ->select('users.id')
//                ->get();
//
//            // Load all grades
//            $grades = GradeLevel::get();
//
//            // Group streams by grade
//            $streamsByGrade = Stream::get()->groupBy('grade_level_id');
//
//            foreach ($students as $student) {
//
//                // Random grade
//                $grade = $grades->random();
//
//                // Streams for that grade
//                $streams = $streamsByGrade[$grade->id] ?? collect();
//
//                if ($streams->isEmpty()) {
//                    continue; // Skip if no streams exist
//                }
//
//                // Random stream
//                $stream = $streams->random();
//
//                $pathwayId = null;
//
//                // Senior grades require pathway
//                if ($grade->sequence > 11) {
//                    $pathwayId = $stream->pathway_id;
//                }
//
//                StudentEnrollment::create([
//                    'user_id'         => $student->id,
//                    'grade_level_id'  => $grade->id,
//                    'stream_id'       => $stream->id,
//                    'pathway_id'      => $pathwayId,
//                    'academic_year'   => date('Y'),
//                    'status'          => 'active',
//                    'enrolled_on'     => now(),
//                ]);
//            }
//
//        });*/
//
//        $user = $request->user();
//        $scoped = $this->teacherScopedAssignments($user);
//
//        $query = Assessment::query()->with(['learningArea', 'stream.gradeLevel', 'academicTerm', 'assessmentType']);
//
//        if ($scoped !== null) {
//            if ($scoped->isEmpty()) {
//                $query->whereRaw('1 = 0'); // no assignments = sees nothing, not everything
//            } else {
//                $query->where(function ($q) use ($scoped) {
//                    foreach ($scoped as $s) {
//                        $q->orWhere(fn ($qq) => $qq->where('learning_area_id', $s->learning_area_id)->where('stream_id', $s->stream_id));
//                    }
//                });
//            }
//        }
//
//        if ($termId = $request->query('academic_term')) {
//            $query->where('academic_term_id', $termId);
//        }
//
//        $assessments = $query->latest('assessment_date')->get();
//        $academicTerms = AcademicTerm::query()->orderByDesc('academic_year')->orderByDesc('term_number')->get();
//
//        return view('results.assessments.index', compact('assessments', 'academicTerms'));
//    }

//    public function data(Request $request): JsonResponse
//    {
//        $user = $request->user();
//        $scoped = $this->teacherScopedAssignments($user);
//
//        $query = Assessment::query()->with(['learningArea', 'stream.gradeLevel', 'academicTerm', 'assessmentType']);
//
//        if ($scoped !== null) {
//            if ($scoped->isEmpty()) {
//                $query->whereRaw('1 = 0');
//            } else {
//                $query->where(function ($q) use ($scoped) {
//                    foreach ($scoped as $s) {
//                        $q->orWhere(fn ($qq) => $qq->where('learning_area_id', $s->learning_area_id)->where('stream_id', $s->stream_id));
//                    }
//                });
//            }
//        }
//
//        if ($termId = $request->query('filter_term')) {
//            $query->where('academic_term_id', $termId);
//        }
//
//        $recordsTotal = (clone $query)->count();
//
//        // DataTables sends the global search box value nested under search[value]
//        $searchValue = $request->query('search')['value'] ?? null;
//
//        if (! empty($searchValue)) {
//            $query->where(function ($q) use ($searchValue) {
//                $q->where('assessments.name', 'like', "%{$searchValue}%")
//                    ->orWhereHas('learningArea', fn ($qq) => $qq->where('name', 'like', "%{$searchValue}%"))
//                    ->orWhereHas('stream', function ($qq) use ($searchValue) {
//                        $qq->where('name', 'like', "%{$searchValue}%")
//                            ->orWhereHas('gradeLevel', fn ($g) => $g->where('name', 'like', "%{$searchValue}%"));
//                    })
//                    ->orWhereHas('assessmentType', fn ($qq) => $qq->where('name', 'like', "%{$searchValue}%"))
//                    ->orWhereHas('academicTerm', function ($qq) use ($searchValue) {
//                        $qq->where('academic_year', 'like', "%{$searchValue}%")
//                            ->orWhere('term_number', 'like', "%{$searchValue}%")
//                            ->orWhereRaw(
//                                "CONCAT(academic_year, ' T', term_number) LIKE ?",
//                                ["%{$searchValue}%"]
//                            )
//                            ->orWhereRaw(
//                                "CONCAT(academic_year, ' Term ', term_number) LIKE ?",
//                                ["%{$searchValue}%"]
//                            );
//                    });
//            });
//        }
//
//        $recordsFiltered = empty($searchValue) ? $recordsTotal : (clone $query)->count();
//
//        $draw = (int) $request->query('draw', 1);
//        $start = (int) $request->query('start', 0);
//        $length = (int) $request->query('length', 25);
//
//// --- Ordering (was missing entirely) ---
//        $orderIndex = $request->query('order')[0]['column'] ?? null;
//        $orderDir = strtolower($request->query('order')[0]['dir'] ?? 'asc') === 'desc' ? 'desc' : 'asc';
//
//// Maps to the `columns` array in assessments-index.js (0 = sn, 8 = actions are non-orderable)
//        switch ($orderIndex) {
//            case 1: // name
//                $query->orderBy('assessments.name', $orderDir);
//                break;
//            case 2: // subject
//                $query->join('learning_areas', 'learning_areas.id', '=', 'assessments.learning_area_id')
//                    ->orderBy('learning_areas.name', $orderDir)
//                    ->select('assessments.*');
//                break;
//            case 3: // class
//                $query->join('streams', 'streams.id', '=', 'assessments.stream_id')
//                    ->join('grade_levels', 'grade_levels.id', '=', 'streams.grade_level_id')
//                    ->orderBy('grade_levels.name', $orderDir)
//                    ->orderBy('streams.name', $orderDir)
//                    ->select('assessments.*');
//                break;
//            case 4: // term
//                $query->join('academic_terms', 'academic_terms.id', '=', 'assessments.academic_term_id')
//                    ->orderBy('academic_terms.academic_year', $orderDir)
//                    ->orderBy('academic_terms.term_number', $orderDir)
//                    ->select('assessments.*');
//                break;
//            case 5: // type
//                $query->join('assessment_types', 'assessment_types.id', '=', 'assessments.assessment_type_id')
//                    ->orderBy('assessment_types.name', $orderDir)
//                    ->select('assessments.*');
//                break;
//            case 6: // max_score
//                $query->orderBy('assessments.max_score', $orderDir);
//                break;
//            case 7: // status
//                $query->orderBy('assessments.status', $orderDir);
//                break;
//            default:
//                $query->latest('assessment_date');
//                break;
//        }
//
//        $assessments = $query->skip($start)->take($length)->get();
//
//        $canManage = $user->hasPermission('curriculum.manage') || $user->hasPermission('results.enter_marks');
//        $statusMap = ['draft' => 'secondary', 'open' => 'success', 'locked' => 'warning', 'void' => 'danger'];
//
//        $data = $assessments->values()->map(function ($a, $i) use ($start, $statusMap, $canManage) {
//            $badgeClass = $statusMap[$a->status] ?? 'secondary';
//
//            $actions = '<a href="' . route('results.assessments.marks-entry', $a->id) . '" class="btn btn-sm btn-outline-primary me-1"><i class="bi bi-pencil-square"></i> Marks</a>';
//
//            if ($canManage) {
//                if ($a->status === 'draft') {
//                    $actions .= '<form method="POST" action="' . route('results.assessments.open', $a->id) . '" class="d-inline open-assessment-form">'
//                        . csrf_field() . '<button type="submit" class="btn btn-sm btn-outline-success">Open</button></form>';
//                } elseif ($a->status === 'open') {
//                    $actions .= '<form method="POST" action="' . route('results.assessments.lock', $a->id) . '" class="d-inline lock-assessment-form">'
//                        . csrf_field() . '<button type="submit" class="btn btn-sm btn-outline-warning">Lock</button></form>';
//                }
//            }
//
//            return [
//                'sn'           => $start + $i + 1,
//                'name'         => $a->name,
//                'subject'      => $a->learningArea->name,
//                'class'        => $a->stream->full_name,
//                'term'         => $a->academicTerm->academic_year . ' T' . $a->academicTerm->term_number,
//                'type'         => $a->assessmentType->name,
//                'max_score'    => $a->max_score ?? '-',
//                'status'       => $a->status,
//                'status_badge' => $badgeClass,
//                'actions'      => $actions,
//            ];
//        });
//
//        return response()->json([
//            'draw'            => $draw,
//            'recordsTotal'    => $recordsTotal,
//            'recordsFiltered' => $recordsFiltered,
//            'data'            => $data,
//        ]);
//    }

    public function index(Request $request)
    {
        $user = $request->user();
        $scoped = $this->teacherScopedAssignments($user);

        $query = Assessment::query()->with('academicTerm');

        if ($scoped !== null) {
            if ($scoped->isEmpty()) {
                $query->whereRaw('1 = 0');
            } else {
                $query->where(function ($q) use ($scoped) {
                    foreach ($scoped as $s) {
                        $q->orWhere(fn ($qq) => $qq->where('learning_area_id', $s->learning_area_id)->where('stream_id', $s->stream_id));
                    }
                });
            }
        }

        if ($termId = $request->query('academic_term')) {
            $query->where('academic_term_id', $termId);
        }

        $rounds = $query->get()
            ->groupBy(fn ($a) => $a->name . '|' . $a->academic_term_id)
            ->map(function ($group) {
                $first = $group->first();
                return [
                    'name'             => $first->name,
                    'academic_term_id' => $first->academic_term_id,
                    'term_label'       => $first->academicTerm->academic_year . ' T' . $first->academicTerm->term_number,
                    'total'            => $group->count(),
                    'draft'            => $group->where('status', 'draft')->count(),
                    'open'             => $group->where('status', 'open')->count(),
                    'locked'           => $group->where('status', 'locked')->count(),
                ];
            })
            ->sortByDesc(fn ($r) => $r['academic_term_id'])
            ->values();

        // Combos already finalized (TermSubjectResult.finalized_at set) shouldn't
        // clutter this panel - it's for what still needs action, not a full history.
        $finalizedCombos = \App\Models\TermSubjectResult::query()
            ->whereNotNull('finalized_at')
            ->join('student_enrollments', 'student_enrollments.id', '=', 'term_subject_results.student_enrollment_id')
            ->select('term_subject_results.learning_area_id', 'student_enrollments.stream_id', 'term_subject_results.academic_term_id')
            ->distinct()->get()
            ->map(fn ($r) => $r->learning_area_id . '|' . $r->stream_id . '|' . $r->academic_term_id)
            ->flip();

        $subjectResultGroups = $query->with(['learningArea', 'stream'])->get()
            ->groupBy(fn ($a) => $a->learning_area_id . '|' . $a->stream_id . '|' . $a->academic_term_id)
            ->reject(fn ($group, $key) => isset($finalizedCombos[$key]))
            ->map(function ($group) {
                $first = $group->first();
                return [
                    'learning_area_id' => $first->learning_area_id,
                    'stream_id'        => $first->stream_id,
                    'academic_term_id' => $first->academic_term_id,
                    'subject'          => $first->learningArea->name,
                    'class'            => $first->stream->full_name,
                    'term'             => $first->academicTerm->academic_year . ' T' . $first->academicTerm->term_number,
                    'assessment_count' => $group->count(),
                ];
            })
            ->values();

        $academicTerms = AcademicTerm::query()->orderByDesc('academic_year')->orderByDesc('term_number')->get();

        return view('results.assessments.index', compact('rounds', 'academicTerms', 'subjectResultGroups'));
    }

    public function roundShow(Request $request, AcademicTerm $academicTerm, string $name)
    {
        $name = urldecode($name);
        return view('results.assessments.round', compact('academicTerm', 'name'));
    }

    public function roundData(Request $request, AcademicTerm $academicTerm, string $name): JsonResponse
    {
        $name = urldecode($name);
        $user = $request->user();
        $scoped = $this->teacherScopedAssignments($user);

        $query = Assessment::query()
            ->where('academic_term_id', $academicTerm->id)
            ->where('assessments.name', $name)
            ->with(['learningArea', 'stream.gradeLevel', 'assessmentType']);

        if ($scoped !== null) {
            if ($scoped->isEmpty()) {
                $query->whereRaw('1 = 0');
            } else {
                $query->where(function ($q) use ($scoped) {
                    foreach ($scoped as $s) {
                        $q->orWhere(fn ($qq) => $qq->where('learning_area_id', $s->learning_area_id)->where('stream_id', $s->stream_id));
                    }
                });
            }
        }

        $recordsTotal = (clone $query)->count();

        $searchValue = $request->query('search')['value'] ?? null;

        if (! empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->orWhereHas('learningArea', fn ($qq) => $qq->where('name', 'like', "%{$searchValue}%"))
                    ->orWhereHas('stream', function ($qq) use ($searchValue) {
                        $qq->where('name', 'like', "%{$searchValue}%")
                            ->orWhereHas('gradeLevel', fn ($g) => $g->where('name', 'like', "%{$searchValue}%"));
                    })
                    ->orWhereHas('assessmentType', fn ($qq) => $qq->where('name', 'like', "%{$searchValue}%"));
            });
        }

        $recordsFiltered = empty($searchValue) ? $recordsTotal : (clone $query)->count();

        $draw = (int) $request->query('draw', 1);
        $start = (int) $request->query('start', 0);
        $length = (int) $request->query('length', 25);

        $orderIndex = $request->query('order')[0]['column'] ?? null;
        $orderDir = strtolower($request->query('order')[0]['dir'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

        switch ($orderIndex) {
            case 1: // subject
                $query->join('learning_areas', 'learning_areas.id', '=', 'assessments.learning_area_id')
                    ->orderBy('learning_areas.name', $orderDir)->select('assessments.*');
                break;
            case 2: // class
                $query->join('streams', 'streams.id', '=', 'assessments.stream_id')
                    ->join('grade_levels', 'grade_levels.id', '=', 'streams.grade_level_id')
                    ->orderBy('grade_levels.name', $orderDir)->orderBy('streams.name', $orderDir)->select('assessments.*');
                break;
            case 3: // type
                $query->join('assessment_types', 'assessment_types.id', '=', 'assessments.assessment_type_id')
                    ->orderBy('assessment_types.name', $orderDir)->select('assessments.*');
                break;
            case 4: // max_score
                $query->orderBy('assessments.max_score', $orderDir);
                break;
            case 5: // status
                $query->orderBy('assessments.status', $orderDir);
                break;
            default:
                $query->join('learning_areas', 'learning_areas.id', '=', 'assessments.learning_area_id')
                    ->orderBy('learning_areas.name', 'asc')->select('assessments.*');
                break;
        }

        $assessments = $query->skip($start)->take($length)->get();

        $canManage = $user->hasPermission('curriculum.manage') || $user->hasPermission('results.enter_marks');
        $statusMap = ['draft' => 'secondary', 'open' => 'success', 'locked' => 'warning', 'void' => 'danger'];

        $finalizedCombos = \App\Models\TermSubjectResult::query()
            ->whereNotNull('finalized_at')
            ->join('student_enrollments', 'student_enrollments.id', '=', 'term_subject_results.student_enrollment_id')
            ->select('term_subject_results.learning_area_id', 'student_enrollments.stream_id', 'term_subject_results.academic_term_id')
            ->distinct()->get()
            ->map(fn ($r) => $r->learning_area_id . '|' . $r->stream_id . '|' . $r->academic_term_id)
            ->flip();

        $data = $assessments->values()->map(function ($a, $i) use ($start, $statusMap, $canManage, $finalizedCombos) {
            $badgeClass = $statusMap[$a->status] ?? 'secondary';
            $actions = '<a href="' . route('results.assessments.marks-entry', $a->id) . '" class="btn btn-sm btn-outline-primary me-1"><i class="bi bi-pencil-square"></i> Marks</a>';

            if ($canManage) {
                if ($a->status === 'draft') {
                    $actions .= '<form method="POST" action="' . route('results.assessments.open', $a->id) . '" class="d-inline open-assessment-form">' . csrf_field() . '<button type="submit" class="btn btn-sm btn-outline-success">Open</button></form>';
                } elseif ($a->status === 'open') {
                    $actions .= '<form method="POST" action="' . route('results.assessments.lock', $a->id) . '" class="d-inline lock-assessment-form">' . csrf_field() . '<button type="submit" class="btn btn-sm btn-outline-warning">Lock</button></form>';
                }
            }

            $isFinalized = isset($finalizedCombos[$a->learning_area_id . '|' . $a->stream_id . '|' . $a->academic_term_id]);

            return [
                'sn'           => $start + $i + 1,
                'subject'      => $a->learningArea->name,
                'class'        => $a->stream->full_name,
                'type'         => $a->assessmentType->name,
                'max_score'    => $a->max_score ?? '-',
                'status'       => $a->status,
                'status_badge' => $badgeClass,
                'finalized'    => $isFinalized
                    ? '<span class="badge bg-success-subtle text-success"><i class="bi bi-check-circle me-1"></i>Finalized</span>'
                    : '<span class="badge bg-light text-muted">Pending</span>',
                'actions'      => $actions,
            ];
        });

        return response()->json([
            'draw' => $draw, 'recordsTotal' => $recordsTotal, 'recordsFiltered' => $recordsFiltered, 'data' => $data,
        ]);
    }
    public function create(Request $request)
    {
        $user = $request->user();
        $scoped = $this->teacherScopedAssignments($user);

        if ($scoped !== null) {
            $learningAreas = LearningArea::query()->whereIn('id', $scoped->pluck('learning_area_id'))->get();
            $streams = Stream::query()
                            ->whereIn('id', $scoped->pluck('stream_id'))
                            ->with(['gradeLevel', 'pathway', 'classTeacher'])
                            ->join('grade_levels', 'grade_levels.id', '=', 'streams.grade_level_id')
                            ->orderBy('grade_levels.sequence')
                            ->orderBy('streams.name')
                            ->select('streams.*')
                            ->get();
        } else {
            $learningAreas = LearningArea::query()->where('status', 'active')->orderBy('name')->get();
            $streams = Stream::query()
                        ->with(['gradeLevel', 'pathway', 'classTeacher'])
                        ->join('grade_levels', 'grade_levels.id', '=', 'streams.grade_level_id')
                        ->orderBy('grade_levels.sequence')
                        ->orderBy('streams.name')
                        ->select('streams.*')
                        ->get();
        }

        $academicTerms = AcademicTerm::query()->orderByDesc('academic_year')->orderByDesc('term_number')->get();
        $assessmentTypes = AssessmentType::query()->where('status', 'active')->orderBy('name')->get();

        return view('results.assessments.create', compact('learningAreas', 'streams', 'academicTerms', 'assessmentTypes'));
    }

    public function store(StoreAssessmentRequest $request)
    {
        $user = $request->user();
        $scoped = $this->teacherScopedAssignments($user);

        $validated = $request->validated();
        $type = AssessmentType::query()->find($validated['assessment_type_id']);

        $maxScore = $validated['max_score'] ?? null;
        if ($type->scoring_mode === 'competency') {
            $maxScore = null;
        } elseif (empty($maxScore)) {
            $maxScore = $type->default_max_score;
        }

        $created = [];
        $skipped = [];

        foreach ($validated['learning_area_id'] as $learningAreaId) {
            foreach ($validated['stream_id'] as $streamId) {
                if ($scoped !== null) {
                    $allowed = $scoped->contains(fn ($s) => $s->learning_area_id === $learningAreaId && $s->stream_id === $streamId);
                    if (! $allowed) {
                        $skipped[] = [$learningAreaId, $streamId];
                        continue; // silently skip combos the teacher isn't assigned to, rather than aborting the whole batch
                    }
                }

                $created[] = Assessment::query()->create([
                    'name'               => $validated['name'],
                    'learning_area_id'   => $learningAreaId,
                    'stream_id'          => $streamId,
                    'academic_term_id'   => $validated['academic_term_id'],
                    'assessment_type_id' => $validated['assessment_type_id'],
                    'max_score'          => $maxScore,
                    'assessment_date'    => $validated['assessment_date'] ?? null,
                    'status'             => 'draft',
                    'created_by'         => $user->id,
                ]);
            }
        }

        if (empty($created)) {
            return back()->withInput()->with('error', 'You are not assigned to teach any of the selected subject/class combinations.');
        }

        $message = count($created) . ' assessment(s) created.';
        if (! empty($skipped)) {
            $message .= ' ' . count($skipped) . ' combination(s) were skipped because you are not assigned to teach them.';
        }

        // With multiple assessments created, there's no single "the" assessment to jump into for marks entry —
        // send them to the index instead, filtered to what was just created if you want that later.
        return redirect()->route('results.assessments.index')->with('success', $message);
    }

    public function open(Assessment $assessment)
    {
        abort_unless(request()->user()?->hasPermission('results.enter_marks') || request()->user()?->hasPermission('curriculum.manage'), 403);
        $assessment->update(['status' => 'open']);
        return back()->with('success', 'Assessment opened for marks entry.');
    }

    public function lock(Assessment $assessment)
    {
        abort_unless(request()->user()?->hasPermission('results.enter_marks') || request()->user()?->hasPermission('curriculum.manage'), 403);
        $assessment->update(['status' => 'locked']);
        return back()->with('success', 'Assessment locked. Marks entry is now closed for this assessment.');
    }

    public function destroy(Assessment $assessment): JsonResponse
    {
        abort_unless(request()->user()?->hasPermission('curriculum.manage'), 403);

        if ($assessment->status === 'locked') {
            return response()->json(['success' => false, 'message' => 'Locked assessments cannot be deleted - void it instead if entered in error.'], 422);
        }

        $assessment->results()->delete();
        $assessment->delete();

        return response()->json(['success' => true, 'message' => 'Assessment deleted.']);
    }
}
