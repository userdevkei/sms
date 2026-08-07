<?php

namespace App\Services\Curriculum;

use App\Models\AcademicTerm;
use App\Models\TermResultCompletion;
use App\Models\User;
use Carbon\Carbon;

class ProgressionEligibilityService
{
    /**
     * Progression is only allowed after Term 3 of $academicYear has ended,
     * and before Term 1 of the following year has started. Missing term
     * dates means the window is treated as closed — absence of proof is
     * not proof of being open.
     */
    public function windowStatus(string $academicYear): array
    {
        $term3 = AcademicTerm::query()->where('academic_year', $academicYear)->where('term_number', 3)->first();
        $nextYear = (string) ((int) $academicYear + 1);
        $nextTerm1 = AcademicTerm::query()->where('academic_year', $nextYear)->where('term_number', 1)->first();

        if (! $term3 || ! $nextTerm1) {
            return [
                'open'   => false,
                'reason' => "Term 3 dates for {$academicYear} and/or Term 1 dates for {$nextYear} haven't been set up yet under Curriculum \u{2192} Academic Terms.",
            ];
        }

        $now = Carbon::now();

        if ($now->lt($term3->end_date)) {
            return ['open' => false, 'reason' => "Term 3 of {$academicYear} doesn't end until " . $term3->end_date->format('d M Y') . '.'];
        }

        if ($now->gte($nextTerm1->start_date)) {
            return ['open' => false, 'reason' => "Term 1 of {$nextYear} has already started (" . $nextTerm1->start_date->format('d M Y') . ') \u{2014} the progression window has closed.'];
        }

        return ['open' => true, 'reason' => null];
    }

    /**
     * Checks term_result_completions — populated automatically by the Results
     * module's report-card publishing, or manually via the fallback screen.
     */
    public function hasCompletedAllTerms(User $student, string $academicYear): bool
    {
        $completed = TermResultCompletion::query()
            ->where('user_id', $student->id)
            ->where('academic_year', $academicYear)
            ->whereNotNull('completed_at')
            ->pluck('term_number');

        return collect([1, 2, 3])->every(fn ($term) => $completed->contains($term));
    }

    public function missingTerms(User $student, string $academicYear): array
    {
        $completed = TermResultCompletion::query()
            ->where('user_id', $student->id)
            ->where('academic_year', $academicYear)
            ->whereNotNull('completed_at')
            ->pluck('term_number')
            ->all();

        return collect([1, 2, 3])->reject(fn ($term) => in_array($term, $completed))->values()->all();
    }
}
