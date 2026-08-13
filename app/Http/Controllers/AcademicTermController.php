<?php

namespace App\Http\Controllers;

use App\Models\AcademicTerm;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class AcademicTermController extends Controller
{
    public function index()
    {
        $terms = AcademicTerm::orderByDesc('academic_year')->orderByDesc('term_number')->get();

        return view('curriculum.academic-terms.index', compact('terms'));
    }

    public function store(Request $request)
    {
        abort_unless($request->user()?->hasPermission('curriculum.manage'), 403);

        $validated = $this->validateTerm($request);

        AcademicTerm::create($validated);

        return redirect()->route('curriculum.academic-terms.index')->with('success', 'Term added successfully.');
    }

    public function update(Request $request, AcademicTerm $academicTerm)
    {
        abort_unless($request->user()?->hasPermission('curriculum.manage'), 403);

        $validated = $this->validateTerm($request, $academicTerm->id);

        $academicTerm->update($validated);

        return redirect()->route('curriculum.academic-terms.index')->with('success', 'Term updated successfully.');
    }

    public function destroy(Request $request, AcademicTerm $academicTerm)
    {
        abort_unless($request->user()?->hasPermission('curriculum.manage'), 403);

        $academicTerm->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Shared validation for store/update: field rules, unique (academic_year, term_number)
     * ignoring the current record when editing, and a date-overlap check against every
     * other term in the table (terms should never share any days, regardless of year/term_number).
     */
    protected function validateTerm(Request $request, ?string $ignoreId = null): array
    {
        $validated = $request->validate([
            'academic_year' => ['required', 'string', 'max:9'],
            'term_number'   => ['required', 'integer', 'in:1,2,3'],
            'start_date'    => ['required', 'date'],
            'end_date'      => ['required', 'date', 'after:start_date'],
        ]);

        Validator::make($validated, [])->after(function ($validator) use ($validated, $ignoreId) {
            // Duplicate (academic_year, term_number) check — friendly message instead of a DB crash.
            $duplicate = AcademicTerm::where('academic_year', $validated['academic_year'])
                ->where('term_number', $validated['term_number'])
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->exists();

            if ($duplicate) {
                $validator->errors()->add(
                    'academic_year',
                    "{$validated['academic_year']} Term {$validated['term_number']} already exists — edit that term instead of creating a new one."
                );
            }

            // Overlap check — no two terms should share any calendar days.
            $overlaps = AcademicTerm::when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->where('start_date', '<=', $validated['end_date'])
                ->where('end_date', '>=', $validated['start_date'])
                ->exists();

            if ($overlaps) {
                $validator->errors()->add('start_date', 'These dates overlap with an existing term.');
            }
        })->validate();

        return $validated;
    }
}
