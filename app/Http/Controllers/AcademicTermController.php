<?php

namespace App\Http\Controllers;

use App\Models\AcademicTerm;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AcademicTermController extends Controller
{
    public function index()
    {
        $terms = AcademicTerm::query()->orderBy('academic_year')->orderBy('term_number')->get();

        return view('curriculum.academic-terms.index', compact('terms'));
    }

    public function store(Request $request)
    {
        abort_unless($request->user()?->hasPermission('curriculum.manage'), 403);

        $validated = $request->validate([
            'academic_year' => ['required', 'string', 'max:9'],
            'term_number'   => ['required', 'integer', 'in:1,2,3'],
            'start_date'    => ['required', 'date'],
            'end_date'      => ['required', 'date', 'after:start_date'],
        ]);

        AcademicTerm::query()->updateOrCreate(
            ['academic_year' => $validated['academic_year'], 'term_number' => $validated['term_number']],
            $validated
        );

        return redirect()->route('curriculum.academic-terms.index')->with('success', 'Term saved successfully.');
    }

    public function destroy(AcademicTerm $academicTerm): JsonResponse
    {
        abort_unless(request()->user()?->hasPermission('curriculum.manage'), 403);

        $academicTerm->delete();

        return response()->json(['success' => true, 'message' => 'Term deleted successfully.']);
    }
}
