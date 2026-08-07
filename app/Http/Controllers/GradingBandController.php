<?php

namespace App\Http\Controllers;

use App\Models\GradingBand;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class GradingBandController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()?->hasPermission('curriculum.manage') || $request->user()?->hasPermission('results.view'), 403);

        $bands = GradingBand::query()->orderBy('min_score')->get();

        return view('results.grading-bands.index', compact('bands'));
    }

    public function store(Request $request)
    {
        abort_unless($request->user()?->hasPermission('curriculum.manage'), 403);

        $validated = $this->validated($request);
        $this->guardAgainstOverlap($validated);

        GradingBand::query()->create($validated);

        return back()->with('success', 'Grading band added.');
    }

    public function update(Request $request, GradingBand $gradingBand)
    {
        abort_unless($request->user()?->hasPermission('curriculum.manage'), 403);

        $validated = $this->validated($request);
        $this->guardAgainstOverlap($validated, $gradingBand->id);

        $gradingBand->update($validated);

        return back()->with('success', 'Grading band updated.');
    }

    public function destroy(Request $request, GradingBand $gradingBand)
    {
        abort_unless($request->user()?->hasPermission('curriculum.manage'), 403);

        $gradingBand->delete();

        return back()->with('success', 'Grading band deleted.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'min_score'    => ['required', 'numeric', 'min:0', 'max:100', 'lt:max_score'],
            'max_score'    => ['required', 'numeric', 'min:0', 'max:100', 'gt:min_score'],
            'letter_grade' => ['required', 'string', 'max:5'],
            'points'       => ['nullable', 'numeric', 'min:0'],
            'remark'       => ['nullable', 'string', 'max:255'],
        ]);
    }

    /**
     * GradingBand::letterFor() matches on min_score <= score <= max_score and
     * takes the first hit via ->value() - if two bands overlap, which one wins
     * is arbitrary (whatever the DB returns first), so overlaps must be blocked
     * here rather than left to that lookup.
     */
    private function guardAgainstOverlap(array $validated, ?string $ignoreId = null): void
    {
        $overlap = GradingBand::query()
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->where('min_score', '<=', $validated['max_score'])
            ->where('max_score', '>=', $validated['min_score'])
            ->exists();

        if ($overlap) {
            throw ValidationException::withMessages([
                'min_score' => 'This range overlaps with an existing grading band.',
            ]);
        }
    }
}
