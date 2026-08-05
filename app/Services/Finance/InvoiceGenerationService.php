<?php
//
//namespace App\Services\Finance;
//
//use App\Models\Exemption;
//use App\Models\FeeStructure;
//use App\Models\Invoice;
//use App\Models\OtherCharge;
//use App\Models\RoomAllocation;
//use App\Models\StudentEnrollment;
//use App\Models\StudentRouteStop;
//use App\Models\User;
//use Illuminate\Support\Facades\DB;
//
//class InvoiceGenerationService
//{
//    /**
//     * Creates one student's invoice for a given term, pulling every source
//     * of charge automatically:
//     *   1. The published fee structure for their grade level (tuition, activity, etc.)
//     *   2. Transport fee, if they're assigned to a route stop this term
//     *   3. Accommodation fee, if they have an active room allocation this term
//     *   4. Any "other charges" scoped to them individually, their stream, or their grade
//     *   5. Approved exemptions, applied as negative line items
//     *
//     * Create-only: if an invoice already exists for this student/academic_year/term,
//     * this is a skip — returns null, existing invoice untouched.
//     *
//     * Also skips (returns null, nothing written) if:
//     *   - the student's grade level has no published fee structure, or
//     *   - the computed total across all charge sources is 0 (e.g. exemptions
//     *     wipe out the full amount) — a zero-value invoice is not created.
//     */
//    public function generateForStudent(User $student, string $academicYear, int $term, string $generatedByUserId): ?Invoice
//    {
//        $enrollment = StudentEnrollment::query()
//            ->where('user_id', $student->id)
//            ->where('academic_year', $academicYear)
//            ->where('status', 'active')
//            ->first();
//
//        if (! $enrollment) {
//            return null; // not actively enrolled this year
//        }
//
//        $alreadyExists = Invoice::query()
//            ->where('user_id', $student->id)
//            ->where('academic_year', $academicYear)
//            ->where('term', $term)
//            ->exists();
//
//        if ($alreadyExists) {
//            return null; // create-only — existing invoice left untouched
//        }
//
//        $feeStructure = FeeStructure::query()
//            ->where('grade_level_id', $enrollment->grade_level_id)
//            ->where('status', 'published')
//            ->with('items.votehead')
//            ->first();
//
//        if (! $feeStructure) {
//            return null; // no active fee structure for this grade — nothing to invoice
//        }
//
//        // Gather every charge line in memory first, so we know the final
//        // total before deciding whether to persist anything at all.
//        $lines = [];
//        $total = 0;
//
//        foreach ($feeStructure->items as $item) {
//            $lines[] = [
//                'source_type' => 'fee_structure',
//                'source_id'   => $item->id,
//                'description' => $item->votehead->name,
//                'amount'      => $item->amount,
//            ];
//            $total += $item->amount;
//        }
//
//        $routeStop = StudentRouteStop::query()
//            ->where('user_id', $student->id)
//            ->where('academic_year', $academicYear)
//            ->where('term', $term)
//            ->where('status', 'active')
//            ->with('routeStop.route')
//            ->first();
//
//        if ($routeStop && $routeStop->routeStop->fare > 0) {
//            $lines[] = [
//                'source_type' => 'transport',
//                'source_id'   => $routeStop->id,
//                'description' => 'Transport — ' . $routeStop->routeStop->route->name . ' (' . $routeStop->routeStop->name . ')',
//                'amount'      => $routeStop->routeStop->fare,
//            ];
//            $total += $routeStop->routeStop->fare;
//        }
//
//        $roomAllocation = RoomAllocation::query()
//            ->where('user_id', $student->id)
//            ->where('academic_year', $academicYear)
//            ->where('status', 'active')
//            ->with('room.hostel')
//            ->first();
//
//        if ($roomAllocation) {
//            $fee = $roomAllocation->room->effectiveFeePerTerm();
//            if ($fee > 0) {
//                $lines[] = [
//                    'source_type' => 'accommodation',
//                    'source_id'   => $roomAllocation->id,
//                    'description' => 'Boarding — ' . $roomAllocation->room->full_name,
//                    'amount'      => $fee,
//                ];
//                $total += $fee;
//            }
//        }
//
//        $otherCharges = OtherCharge::query()
//            ->where('academic_year', $academicYear)
//            ->where('term', $term)
//            ->where('status', 'active')
//            ->where(function ($q) use ($student, $enrollment) {
//                $q->where('user_id', $student->id)
//                    ->orWhere('stream_id', $enrollment->stream_id)
//                    ->orWhere('grade_level_id', $enrollment->grade_level_id);
//            })
//            ->with('type')
//            ->get();
//
//        foreach ($otherCharges as $charge) {
//            $lines[] = [
//                'source_type' => 'other_charge',
//                'source_id'   => $charge->id,
//                'description' => $charge->type->name . ' — ' . $charge->description,
//                'amount'      => $charge->amount,
//            ];
//            $total += $charge->amount;
//        }
//
//        $exemptions = Exemption::query()
//            ->where('user_id', $student->id)
//            ->where('academic_year', $academicYear)
//            ->where('term', $term)
//            ->where('status', 'approved')
//            ->with('votehead')
//            ->get();
//
//        foreach ($exemptions as $exemption) {
//            $discount = $exemption->type === 'fixed'
//                ? (float) $exemption->value
//                : round($total * ((float) $exemption->value / 100), 2);
//
//            if ($discount > 0) {
//                $lines[] = [
//                    'source_type' => 'exemption',
//                    'source_id'   => $exemption->id,
//                    'description' => 'Exemption — ' . $exemption->scopeLabel() . ' (' . $exemption->reason . ')',
//                    'amount'      => -$discount,
//                ];
//                $total -= $discount;
//            }
//        }
//
//        $total = max(0, $total);
//
//        if ($total <= 0) {
//            return null; // computed total is zero — no invoice created
//        }
//
//        return DB::transaction(function () use ($student, $enrollment, $academicYear, $term, $generatedByUserId, $lines, $total) {
//            $invoice = Invoice::query()->create([
//                'user_id'        => $student->id,
//                'academic_year'  => $academicYear,
//                'term'           => $term,
//                'invoice_number' => $this->nextInvoiceNumber($academicYear),
//                'grade_level_id' => $enrollment->grade_level_id,
//                'generated_by'   => $generatedByUserId,
//                'due_date'       => now()->addWeeks(2),
//                'total_amount'   => $total,
//                'status'         => 'unpaid',
//            ]);
//
//            foreach ($lines as $line) {
//                $invoice->items()->create($line);
//            }
//
//            $invoice->recalculate(); // sets balance/status against any existing payments
//
//            return $invoice->fresh('items');
//        });
//    }
//
//    /**
//     * Bulk-generate for every actively-enrolled student in a grade level.
//     * Returns counts, not exceptions — a single student's missing data
//     * (no enrollment, no fee structure, zero total, or an invoice that
//     * already exists) never aborts the whole batch.
//     */
//    public function generateForGradeLevel(string $gradeLevelId, string $academicYear, int $term, string $generatedByUserId): array
//    {
//        $studentIds = StudentEnrollment::query()
//            ->where('grade_level_id', $gradeLevelId)
//            ->where('academic_year', $academicYear)
//            ->where('status', 'active')
//            ->pluck('user_id');
//
//        $generated = 0;
//        $skipped = 0;
//
//        foreach ($studentIds as $studentId) {
//            $student = User::query()->find($studentId);
//            $invoice = $student ? $this->generateForStudent($student, $academicYear, $term, $generatedByUserId) : null;
//
//            $invoice ? $generated++ : $skipped++;
//        }
//
//        return ['generated' => $generated, 'skipped' => $skipped, 'total' => $studentIds->count()];
//    }
//
//    private function nextInvoiceNumber(string $academicYear): string
//    {
//        $count = Invoice::query()->where('academic_year', $academicYear)->count() + 1;
//
//        return "INV-{$academicYear}-" . str_pad($count, 6, '0', STR_PAD_LEFT);
//    }
//
//    /**
//     * Read-only check used by the invoice preview screen. Mirrors the skip
//     * conditions in generateForStudent() exactly, but never writes anything —
//     * used to grey out rows and explain why, before generation runs for real.
//     */
//    public function previewForStudent(User $student, string $academicYear, int $term): array
//    {
//        $enrollment = StudentEnrollment::query()
//            ->where('user_id', $student->id)
//            ->where('academic_year', $academicYear)
//            ->where('status', 'active')
//            ->first();
//
//        if (! $enrollment) {
//            return ['ready' => false, 'reason' => 'No active enrollment this year', 'grade_level_name' => '—', 'total' => 0];
//        }
//
//        $alreadyExists = Invoice::query()
//            ->where('user_id', $student->id)
//            ->where('academic_year', $academicYear)
//            ->where('term', $term)
//            ->exists();
//
//        if ($alreadyExists) {
//            return ['ready' => false, 'reason' => 'Already invoiced', 'grade_level_name' => $enrollment->gradeLevel->name ?? '—', 'total' => 0];
//        }
//
//        $feeStructure = FeeStructure::query()
//            ->where('grade_level_id', $enrollment->grade_level_id)
//            ->where('status', 'published')
//            ->with('items')
//            ->first();
//
//        if (! $feeStructure) {
//            return ['ready' => false, 'reason' => 'No active fee structure for this grade', 'grade_level_name' => $enrollment->gradeLevel->name ?? '—', 'total' => 0];
//        }
//
//        // Cheap total estimate for display purposes: fee structure + transport +
//        // accommodation + other charges - exemptions, same sources as the real
//        // generator. Kept separate from generateForStudent() so the preview
//        // never triggers a write, at the cost of duplicating this arithmetic —
//        // if that drift risk bothers you, say so and I'll refactor both methods
//        // to share one "compute lines" helper instead.
//        $total = $feeStructure->items->sum('amount');
//
//        $routeStop = StudentRouteStop::query()
//            ->where('user_id', $student->id)->where('academic_year', $academicYear)->where('term', $term)
//            ->where('status', 'active')->with('routeStop')->first();
//        if ($routeStop) $total += $routeStop->routeStop->fare;
//
//        $roomAllocation = RoomAllocation::query()
//            ->where('user_id', $student->id)->where('academic_year', $academicYear)->where('status', 'active')
//            ->with('room')->first();
//        if ($roomAllocation) $total += $roomAllocation->room->effectiveFeePerTerm();
//
//        $otherChargesTotal = OtherCharge::query()
//            ->where('academic_year', $academicYear)->where('term', $term)->where('status', 'active')
//            ->where(fn ($q) => $q->where('user_id', $student->id)
//                ->orWhere('stream_id', $enrollment->stream_id)
//                ->orWhere('grade_level_id', $enrollment->grade_level_id))
//            ->sum('amount');
//        $total += $otherChargesTotal;
//
//        $exemptionsTotal = Exemption::query()
//            ->where('user_id', $student->id)->where('academic_year', $academicYear)->where('term', $term)
//            ->where('status', 'approved')->get()
//            ->sum(fn ($e) => $e->type === 'fixed' ? (float) $e->value : round($total * ((float) $e->value / 100), 2));
//        $total = max(0, $total - $exemptionsTotal);
//
//        if ($total <= 0) {
//            return ['ready' => false, 'reason' => 'Computed total is zero', 'grade_level_name' => $enrollment->gradeLevel->name ?? '—', 'total' => 0];
//        }
//
//        return ['ready' => true, 'reason' => null, 'grade_level_name' => $enrollment->gradeLevel->name ?? '—', 'total' => $total];
//    }
//}


namespace App\Services\Finance;

use App\Models\Exemption;
use App\Models\FeeStructure;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\OtherCharge;
use App\Models\RoomAllocation;
use App\Models\StudentEnrollment;
use App\Models\StudentRouteStop;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class InvoiceGenerationService
{
    /**
     * Creates an invoice covering only the charges NOT already invoiced for
     * this student/academic_year/term. This is create-only in a different
     * sense than before: it never touches an existing invoice, but it also
     * no longer skips just because one exists. If a student was already
     * invoiced for tuition and transport is added afterward, running this
     * again produces a second, supplemental invoice containing only the
     * transport line — the first invoice is untouched.
     *
     * Returns null if:
     *   - the student has no active enrollment this year, or
     *   - the student's grade level has no published fee structure, or
     *   - every chargeable item for this student/year/term has already
     *     appeared on a prior invoice (nothing new to bill).
     */
    public function generateForStudent(User $student, string $academicYear, int $term, string $generatedByUserId): ?Invoice
    {
        $enrollment = StudentEnrollment::query()
            ->where('user_id', $student->id)
            ->where('academic_year', $academicYear)
            ->where('status', 'active')
            ->first();

        if (!$enrollment) {
            return null; // not actively enrolled this year
        }

        $feeStructure = FeeStructure::query()
            ->where('grade_level_id', $enrollment->grade_level_id)
            ->where('status', 'published')
            ->with('items.votehead')
            ->first();

        if (!$feeStructure) {
            return null; // no active fee structure for this grade — nothing to invoice
        }

        $alreadyInvoiced = $this->alreadyInvoicedSourceKeys($student->id, $academicYear, $term);

        [$lines, $total] = $this->buildChargeLines($student, $enrollment, $academicYear, $term, $feeStructure, $alreadyInvoiced);

        if ($total <= 0) {
            return null; // nothing new to bill — every source is already on a prior invoice
        }

        return DB::transaction(function () use ($student, $enrollment, $academicYear, $term, $generatedByUserId, $lines, $total) {
            $invoice = Invoice::query()->create([
                'user_id' => $student->id,
                'academic_year' => $academicYear,
                'term' => $term,
                'invoice_number' => $this->nextInvoiceNumber($academicYear),
                'grade_level_id' => $enrollment->grade_level_id,
                'generated_by' => $generatedByUserId,
                'due_date' => now()->addWeeks(2),
                'total_amount' => $total,
                'status' => 'unpaid',
            ]);

            foreach ($lines as $line) {
                $invoice->items()->create($line);
            }

            $invoice->recalculate();

            return $invoice->fresh('items');
        });
    }

    /**
     * Every (source_type, source_id) pair already billed to this student for
     * this academic_year/term, across ALL their invoices — not just the most
     * recent one. Used to filter out charges that would otherwise be billed
     * twice across an initial + supplemental invoice.
     *
     * Assumption: cancelled invoices don't "hold" their items — if an invoice
     * is cancelled, its charges should be eligible to reappear on a new one.
     * If your Invoice model doesn't have a 'cancelled' status, drop that
     * whereNot clause.
     */
    private function alreadyInvoicedSourceKeys(string $userId, string $academicYear, int $term): Collection
    {
        return InvoiceItem::query()
            ->join('invoices', 'invoices.id', '=', 'invoice_items.invoice_id')
            ->where('invoices.user_id', $userId)
            ->where('invoices.academic_year', $academicYear)
            ->where('invoices.term', $term)
            ->where('invoices.status', '!=', 'cancelled')
            ->get(['invoice_items.source_type', 'invoice_items.source_id'])
            ->map(fn($row) => "{$row->source_type}:{$row->source_id}")
            ->unique()
            ->values();
    }

    /**
     * Builds the charge lines for a student, excluding anything whose
     * (source_type, source_id) is already in $alreadyInvoiced. Exemptions
     * are applied against the total of THESE lines only — i.e. against
     * whatever is newly being billed right now, not the student's
     * cumulative total across all their invoices for the term.
     *
     * @return array{0: array, 1: float} [$lines, $total]
     */
    private function buildChargeLines(
        User              $student,
        StudentEnrollment $enrollment,
        string            $academicYear,
        int               $term,
        FeeStructure      $feeStructure,
        Collection        $alreadyInvoiced
    ): array
    {
        $lines = [];
        $total = 0;

        $isNew = fn(string $type, string $id) => !$alreadyInvoiced->contains("{$type}:{$id}");

        foreach ($feeStructure->items as $item) {
            if (!$isNew('fee_structure', $item->id)) continue;

            $lines[] = [
                'source_type' => 'fee_structure',
                'source_id' => $item->id,
                'description' => $item->votehead->name,
                'amount' => $item->amount,
            ];
            $total += $item->amount;
        }

        $routeStop = StudentRouteStop::query()
            ->where('user_id', $student->id)
            ->where('academic_year', $academicYear)
            ->where('term', $term)
            ->where('status', 'active')
            ->with('routeStop.route')
            ->first();

        if ($routeStop && $routeStop->routeStop->fare > 0 && $isNew('transport', $routeStop->id)) {
            $lines[] = [
                'source_type' => 'transport',
                'source_id' => $routeStop->id,
                'description' => 'Transport — ' . $routeStop->routeStop->route->name . ' (' . $routeStop->routeStop->name . ')',
                'amount' => $routeStop->routeStop->fare,
            ];
            $total += $routeStop->routeStop->fare;
        }

        $roomAllocation = RoomAllocation::query()
            ->where('user_id', $student->id)
            ->where('academic_year', $academicYear)
            ->where('status', 'active')
            ->with('room.hostel')
            ->first();

        if ($roomAllocation) {
            $fee = $roomAllocation->room->effectiveFeePerTerm();
            if ($fee > 0 && $isNew('accommodation', $roomAllocation->id)) {
                $lines[] = [
                    'source_type' => 'accommodation',
                    'source_id' => $roomAllocation->id,
                    'description' => 'Boarding — ' . $roomAllocation->room->full_name,
                    'amount' => $fee,
                ];
                $total += $fee;
            }
        }

        $otherCharges = OtherCharge::query()
            ->where('academic_year', $academicYear)
            ->where('term', $term)
            ->where('status', 'active')
            ->where(function ($q) use ($student, $enrollment) {
                $q->where('user_id', $student->id)
                    ->orWhere('stream_id', $enrollment->stream_id)
                    ->orWhere('grade_level_id', $enrollment->grade_level_id);
            })
            ->with('type')
            ->get();

        foreach ($otherCharges as $charge) {
            if (!$isNew('other_charge', $charge->id)) continue;

            $lines[] = [
                'source_type' => 'other_charge',
                'source_id' => $charge->id,
                'description' => $charge->type->name . ' — ' . $charge->description,
                'amount' => $charge->amount,
            ];
            $total += $charge->amount;
        }

        // Exemptions apply against $total as built up to this point — i.e.
        // against only the newly-collected charges on this invoice.
        $exemptions = Exemption::query()
            ->where('user_id', $student->id)
            ->where('academic_year', $academicYear)
            ->where('term', $term)
            ->where('status', 'approved')
            ->with('votehead')
            ->get();

        foreach ($exemptions as $exemption) {
            if (!$isNew('exemption', $exemption->id)) continue;

            $discount = $exemption->type === 'fixed'
                ? (float)$exemption->value
                : round($total * ((float)$exemption->value / 100), 2);

            if ($discount > 0) {
                $lines[] = [
                    'source_type' => 'exemption',
                    'source_id' => $exemption->id,
                    'description' => 'Exemption — ' . $exemption->scopeLabel() . ' (' . $exemption->reason . ')',
                    'amount' => -$discount,
                ];
                $total -= $discount;
            }
        }

        return [$lines, max(0, $total)];
    }

    /**
     * Bulk-generate for every actively-enrolled student in a grade level.
     * Returns counts, not exceptions — a single student's missing data
     * (no enrollment, no fee structure, or nothing new to bill) never
     * aborts the whole batch.
     */
    public function generateForGradeLevel(string $gradeLevelId, string $academicYear, int $term, string $generatedByUserId): array
    {
        $studentIds = StudentEnrollment::query()
            ->where('grade_level_id', $gradeLevelId)
            ->where('academic_year', $academicYear)
            ->where('status', 'active')
            ->pluck('user_id');

        $generated = 0;
        $skipped = 0;

        foreach ($studentIds as $studentId) {
            $student = User::query()->find($studentId);
            $invoice = $student ? $this->generateForStudent($student, $academicYear, $term, $generatedByUserId) : null;

            $invoice ? $generated++ : $skipped++;
        }

        return ['generated' => $generated, 'skipped' => $skipped, 'total' => $studentIds->count()];
    }

    private function nextInvoiceNumber(string $academicYear): string
    {
        $count = Invoice::query()->where('academic_year', $academicYear)->count() + 1;

        return "INV-{$academicYear}-" . str_pad($count, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Read-only mirror of generateForStudent() for the preview screen.
     * Reuses buildChargeLines() directly — no separately-maintained total
     * calculation this time, so preview and real generation can't drift
     * apart the way the earlier version risked.
     */
    public function previewForStudent(User $student, string $academicYear, int $term): array
    {
        $enrollment = StudentEnrollment::query()
            ->where('user_id', $student->id)
            ->where('academic_year', $academicYear)
            ->where('status', 'active')
            ->first();

        if (!$enrollment) {
            return ['ready' => false, 'reason' => 'No active enrollment this year', 'grade_level_name' => '—', 'total' => 0];
        }

        $feeStructure = FeeStructure::query()
            ->where('grade_level_id', $enrollment->grade_level_id)
            ->where('status', 'published')
            ->with('items.votehead')
            ->first();

        if (!$feeStructure) {
            return ['ready' => false, 'reason' => 'No active fee structure for this grade', 'grade_level_name' => $enrollment->gradeLevel->name ?? '—', 'total' => 0];
        }

        $alreadyInvoiced = $this->alreadyInvoicedSourceKeys($student->id, $academicYear, $term);

        [, $total] = $this->buildChargeLines($student, $enrollment, $academicYear, $term, $feeStructure, $alreadyInvoiced);

        if ($total <= 0) {
            $reason = $alreadyInvoiced->isNotEmpty()
                ? 'Everything chargeable is already invoiced'
                : 'Computed total is zero';

            return ['ready' => false, 'reason' => $reason, 'grade_level_name' => $enrollment->gradeLevel->name ?? '—', 'total' => 0];
        }

        return ['ready' => true, 'reason' => null, 'grade_level_name' => $enrollment->gradeLevel->name ?? '—', 'total' => $total];
    }
}
