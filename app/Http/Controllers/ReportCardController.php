<?php
//
//namespace App\Http\Controllers;
//
//use App\Models\AcademicTerm;
//use App\Models\Stream;
//use App\Models\TermOverallResult;
//use App\Services\Curriculum\ProgressionEligibilityService;
//use App\Services\Results\ReportCardCompiler;
//use Illuminate\Http\Request;
//
//class ReportCardController extends Controller
//{
//    public function index(Request $request)
//    {
//        $streams = Stream::query()
//            ->with(['gradeLevel', 'pathway', 'classTeacher'])
//            ->join('grade_levels', 'grade_levels.id', '=', 'streams.grade_level_id')
//            ->orderBy('grade_levels.sequence')
//            ->orderBy('streams.name')
//            ->select('streams.*')
//            ->get();
//        $academicTerms = AcademicTerm::query()->orderByDesc('academic_year')->orderByDesc('term_number')->get();
//
//        return view('results.report-cards.index', compact('streams', 'academicTerms'));
//    }
//
//    public function compile(Request $request, ReportCardCompiler $compiler)
//    {
//        abort_unless($request->user()?->hasPermission('results.approve') || $request->user()?->hasPermission('curriculum.manage'), 403);
//
////        return $request->all();
//
//        $validated = $request->validate([
//            'stream_id'        => ['required', 'string', 'exists:streams,id'],
//            'academic_term_id' => ['required', 'string', 'exists:academic_terms,id'],
//        ]);
//
//        $stream = Stream::findOrFail($validated['stream_id']);
//        $term = AcademicTerm::findOrFail($validated['academic_term_id']);
//
//        $count = $compiler->compileForStream($stream, $term);
//
//        return redirect()->route('results.report-cards.review', [$stream->id, $term->id])
//            ->with('success', "Compiled report cards for {$count} student(s). Review below before publishing.");
//    }
//
//    public function review(Stream $stream, AcademicTerm $academicTerm)
//    {
//        $results = TermOverallResult::query()
//            ->whereHas('enrollment', fn ($q) => $q->where('stream_id', $stream->id))
//            ->where('academic_term_id', $academicTerm->id)
//            ->with('enrollment.student')
//            ->orderBy('position_in_stream')
//            ->get();
//
//        return view('results.report-cards.review', compact('stream', 'academicTerm', 'results'));
//    }
//
//    public function publish(Request $request, ReportCardCompiler $compiler, ProgressionEligibilityService $eligibility)
//    {
//        abort_unless($request->user()?->hasPermission('results.publish') || $request->user()?->hasPermission('curriculum.manage'), 403);
//
//        $validated = $request->validate([
//            'stream_id'        => ['required', 'string', 'exists:streams,id'],
//            'academic_term_id' => ['required', 'string', 'exists:academic_terms,id'],
//        ]);
//
//        $academicTerm = AcademicTerm::findOrFail($validated['academic_term_id']);
//
//        $results = TermOverallResult::query()
//            ->whereHas('enrollment', fn ($q) => $q->where('stream_id', $validated['stream_id']))
//            ->where('academic_term_id', $academicTerm->id)
//            ->where('status', 'draft')
//            ->get();
//
//        foreach ($results as $result) {
//            $result->update(['status' => 'published', 'published_by' => $request->user()->id, 'published_at' => now()]);
//
//            // Bridge to Progression: publishing IS what "term results completed"
//            // means. This replaces the manual checkbox screen for any student
//            // whose results actually get published through this flow.
//            \App\Models\TermResultCompletion::query()->updateOrCreate(
//                ['user_id' => $result->enrollment->user_id, 'academic_year' => $academicTerm->academic_year, 'term_number' => $academicTerm->term_number],
//                ['completed_at' => now(), 'recorded_by' => $request->user()->id]
//            );
//        }
//
//        return redirect()->route('results.report-cards.index')->with('success', "{$results->count()} report card(s) published.");
//    }
//}


//namespace App\Http\Controllers;
//
//use App\Models\AcademicTerm;
//use App\Models\Assessment;
//use App\Models\Stream;
//use App\Models\TermOverallResult;
//use App\Services\Curriculum\ProgressionEligibilityService;
//use App\Services\Results\AssessmentRoundReportBuilder;
//use App\Services\Results\ReportCardCompiler;
//use Illuminate\Http\Request;
//
//class ReportCardController extends Controller
//{
//    public function index(Request $request)
//    {
//        $streams = Stream::query()
//            ->with(['gradeLevel', 'pathway', 'classTeacher'])
//            ->join('grade_levels', 'grade_levels.id', '=', 'streams.grade_level_id')
//            ->orderBy('grade_levels.sequence')
//            ->orderBy('streams.name')
//            ->select('streams.*')
//            ->get();
//        $academicTerms = AcademicTerm::query()->orderByDesc('academic_year')->orderByDesc('term_number')->get();
//
//        return view('results.report-cards.index', compact('streams', 'academicTerms'));
//    }
//
//    /** Distinct assessment round names (e.g. Opener, Mid Term) for a stream+term, used to populate the Exam dropdown. */
//    public function assessmentRounds(Request $request)
//    {
//        $validated = $request->validate([
//            'stream_id' => ['required', 'string', 'exists:streams,id'],
//            'academic_term_id' => ['required', 'string', 'exists:academic_terms,id'],
//        ]);
//
//        $names = Assessment::query()
//            ->where('stream_id', $validated['stream_id'])
//            ->where('academic_term_id', $validated['academic_term_id'])
//            ->distinct()
//            ->pluck('name');
//
//        return response()->json(['rounds' => $names]);
//    }
//
//    public function compile(Request $request, ReportCardCompiler $compiler)
//    {
//        abort_unless($request->user()?->hasPermission('results.approve') || $request->user()?->hasPermission('curriculum.manage'), 403);
//
//        $validated = $request->validate([
//            'stream_id' => ['required', 'string', 'exists:streams,id'],
//            'academic_term_id' => ['required', 'string', 'exists:academic_terms,id'],
//        ]);
//
//        $stream = Stream::findOrFail($validated['stream_id']);
//        $term = AcademicTerm::findOrFail($validated['academic_term_id']);
//
//        $count = $compiler->compileForStream($stream, $term);
//
//        return redirect()->route('results.report-cards.review', [$stream->id, $term->id])
//            ->with('success', "Compiled report cards for {$count} student(s). Review below before publishing.");
//    }
//
//    public function review(Stream $stream, AcademicTerm $academicTerm)
//    {
//        $results = TermOverallResult::query()
//            ->whereHas('enrollment', fn($q) => $q->where('stream_id', $stream->id))
//            ->where('academic_term_id', $academicTerm->id)
//            ->with('enrollment.student')
//            ->orderBy('position_in_stream')
//            ->get();
//
//        return view('results.report-cards.review', compact('stream', 'academicTerm', 'results'));
//    }
//
//    public function publish(Request $request, ProgressionEligibilityService $eligibility)
//    {
//        abort_unless($request->user()?->hasPermission('results.publish') || $request->user()?->hasPermission('curriculum.manage'), 403);
//
//        $validated = $request->validate([
//            'stream_id' => ['required', 'string', 'exists:streams,id'],
//            'academic_term_id' => ['required', 'string', 'exists:academic_terms,id'],
//        ]);
//
//        $academicTerm = AcademicTerm::findOrFail($validated['academic_term_id']);
//
//        $results = TermOverallResult::query()
//            ->whereHas('enrollment', fn($q) => $q->where('stream_id', $validated['stream_id']))
//            ->where('academic_term_id', $academicTerm->id)
//            ->where('status', 'draft')
//            ->get();
//
//        foreach ($results as $result) {
//            $result->update(['status' => 'published', 'published_by' => $request->user()->id, 'published_at' => now()]);
//
//            \App\Models\TermResultCompletion::query()->updateOrCreate(
//                ['user_id' => $result->enrollment->user_id, 'academic_year' => $academicTerm->academic_year, 'term_number' => $academicTerm->term_number],
//                ['completed_at' => now(), 'recorded_by' => $request->user()->id]
//            );
//        }
//
//        // Back to review (not index) so staff immediately sees the now-published statuses.
//        return redirect()->route('results.report-cards.review', [$validated['stream_id'], $academicTerm->id])
//            ->with('success', "{$results->count()} report card(s) published.");
//    }
//
//    public function assessmentReview(Stream $stream, AcademicTerm $academicTerm, string $name, AssessmentRoundReportBuilder $builder)
//    {
//        $name = urldecode($name);
//        $students = $builder->build($stream, $academicTerm, $name);
//
//        return view('results.report-cards.assessment-review', compact('stream', 'academicTerm', 'name', 'students'));
//    }
//}


namespace App\Http\Controllers;

use App\Models\AcademicTerm;
use App\Models\Assessment;
use App\Models\Stream;
use App\Models\TermOverallResult;
use App\Services\Curriculum\ProgressionEligibilityService;
use App\Services\Results\AssessmentRoundReportBuilder;
use App\Services\Results\ReportCardCompiler;
use App\Services\Results\YearReportBuilder;
use Illuminate\Http\Request;

class ReportCardController extends Controller
{
    public function index(Request $request)
    {
        $streams = Stream::query()
            ->with(['gradeLevel', 'pathway', 'classTeacher'])
            ->join('grade_levels', 'grade_levels.id', '=', 'streams.grade_level_id')
            ->orderBy('grade_levels.sequence')
            ->orderBy('streams.name')
            ->select('streams.*')
            ->get();
        $academicTerms = AcademicTerm::query()->orderByDesc('academic_year')->orderByDesc('term_number')->get();
        $academicYears = $academicTerms->pluck('academic_year')->unique()->values();

        return view('results.report-cards.index', compact('streams', 'academicTerms', 'academicYears'));
    }

    public function assessmentRounds(Request $request)
    {
        $validated = $request->validate([
            'stream_id' => ['required', 'string', 'exists:streams,id'],
            'academic_term_id' => ['required', 'string', 'exists:academic_terms,id'],
        ]);

        $names = Assessment::query()
            ->where('stream_id', $validated['stream_id'])
            ->where('academic_term_id', $validated['academic_term_id'])
            ->distinct()
            ->pluck('name');

        return response()->json(['rounds' => $names]);
    }

    public function compile(Request $request, ReportCardCompiler $compiler)
    {
        abort_unless($request->user()?->hasPermission('results.approve') || $request->user()?->hasPermission('curriculum.manage'), 403);

        $validated = $request->validate([
            'stream_id' => ['required', 'string', 'exists:streams,id'],
            'academic_term_id' => ['required', 'string', 'exists:academic_terms,id'],
        ]);

        $stream = Stream::findOrFail($validated['stream_id']);
        $term = AcademicTerm::findOrFail($validated['academic_term_id']);

        $count = $compiler->compileForStream($stream, $term);

        return redirect()->route('results.report-cards.review', [$stream->id, $term->id])
            ->with('success', "Compiled report cards for {$count} student(s). Review below before publishing.");
    }

    public function review(Stream $stream, AcademicTerm $academicTerm)
    {
        $results = TermOverallResult::query()
            ->whereHas('enrollment', fn($q) => $q->where('stream_id', $stream->id))
            ->where('academic_term_id', $academicTerm->id)
            ->with('enrollment.student')
            ->orderBy('position_in_stream')
            ->get();

        return view('results.report-cards.review', compact('stream', 'academicTerm', 'results'));
    }

    public function publish(Request $request, ProgressionEligibilityService $eligibility)
    {
        abort_unless($request->user()?->hasPermission('results.publish') || $request->user()?->hasPermission('curriculum.manage'), 403);

        $validated = $request->validate([
            'stream_id' => ['required', 'string', 'exists:streams,id'],
            'academic_term_id' => ['required', 'string', 'exists:academic_terms,id'],
        ]);

        $academicTerm = AcademicTerm::findOrFail($validated['academic_term_id']);

        $results = TermOverallResult::query()
            ->whereHas('enrollment', fn($q) => $q->where('stream_id', $validated['stream_id']))
            ->where('academic_term_id', $academicTerm->id)
            ->where('status', 'draft')
            ->get();

        foreach ($results as $result) {
            $result->update(['status' => 'published', 'published_by' => $request->user()->id, 'published_at' => now()]);

            \App\Models\TermResultCompletion::query()->updateOrCreate(
                ['user_id' => $result->enrollment->user_id, 'academic_year' => $academicTerm->academic_year, 'term_number' => $academicTerm->term_number],
                ['completed_at' => now(), 'recorded_by' => $request->user()->id]
            );
        }

        // Publishing a stream's report cards means the marks behind them are
        // final — lock every 'open' assessment for this stream/term so marks
        // entry closes. 'draft' assessments are left alone (nothing scored yet
        // to protect), and already-'locked' ones are untouched.
        $lockedCount = \App\Models\Assessment::query()
            ->where('stream_id', $validated['stream_id'])
            ->where('academic_term_id', $academicTerm->id)
            ->where('status', 'open')
            ->update(['status' => 'locked']);

        return redirect()->route('results.report-cards.review', [$validated['stream_id'], $academicTerm->id])
            ->with('success', "{$results->count()} report card(s) published, {$lockedCount} assessment(s) locked.");
    }

    public function assessmentReview(Stream $stream, AcademicTerm $academicTerm, string $name, AssessmentRoundReportBuilder $builder)
    {
        $name = urldecode($name);
        $students = $builder->build($stream, $academicTerm, $name);

        return view('results.report-cards.assessment-review', compact('stream', 'academicTerm', 'name', 'students'));
    }

    public function yearReview(Stream $stream, string $academicYear, YearReportBuilder $builder)
    {
        $students = $builder->build($stream, $academicYear);

        return view('results.report-cards.year-review', compact('stream', 'academicYear', 'students'));
    }
}
