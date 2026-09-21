<?php

namespace App\Services\Finance;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Single source of truth for the debtors report. The on-screen table, the
 * summary cards and both exports all call query(), so they can never disagree.
 */
class DebtorReportService
{
    private array $c;

    public function __construct()
    {
        $this->c = config('finance_reports');
    }

    /* ------------------------------------------------------------------ */
    /*  Core query                                                        */
    /* ------------------------------------------------------------------ */

    /**
     * One row per debtor (balance > 0) with all filters applied.
     * Columns: student_id, admission_no, student_name, grade_id, grade_name,
     * grade_sort, stream_id, stream_name, invoiced, paid, balance, balance_pct.
     */
    public function query(array $f): Builder
    {
        return DB::query()
            ->fromSub($this->studentBalances(), 't')
            ->where('t.balance', '>=', 0.01)
            ->when($f['grade_ids'], fn ($q, $v) => $q->whereIn('t.grade_id', $v))
            ->when($f['stream_ids'], fn ($q, $v) => $q->whereIn('t.stream_id', $v))
            ->when($f['min_pct'] !== null, fn ($q) => $q->where('t.balance_pct', '>=', $f['min_pct']))
            ->when($f['max_pct'] !== null, fn ($q) => $q->where('t.balance_pct', '<=', $f['max_pct']))
            ->when($f['min_balance'] !== null, fn ($q) => $q->where('t.balance', '>=', $f['min_balance']))
            ->when($f['q'] !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('t.student_name', 'like', '%' . $f['q'] . '%')
                ->orWhere('t.admission_no', 'like', '%' . $f['q'] . '%')));
    }

    private function studentBalances(): Builder
    {
        $c = $this->c;
        $s = $c['students'];
        $e = $c['enrollments'];
        $g = $c['grades'];
        $st = $c['streams'];
        $i = $c['invoices'];
        $p = $c['payments'];

        // Aggregate each table separately, then join by student. Joining
        // invoices to payments directly multiplies rows and inflates BOTH totals.
        $invoiced = DB::table($i['table'])
            ->select("{$i['student_fk']} as student_id")
            ->selectRaw("SUM({$i['amount']}) as invoiced")
            ->when($i['soft_deletes'], fn ($q) => $q->whereNull('deleted_at'))
            ->when($i['excluded_statuses'], fn ($q, $v) => $q->whereNotIn($i['status_col'], $v))
            ->groupBy($i['student_fk']);

        $paid = DB::table($p['table'])
            ->select("{$p['student_fk']} as student_id")
            ->selectRaw("SUM({$p['amount']}) as paid")
            ->when($p['soft_deletes'], fn ($q) => $q->whereNull('deleted_at'))
            ->when($p['included_statuses'], fn ($q, $v) => $q->whereIn($p['status_col'], $v))
            ->groupBy($p['student_fk']);

        // Deleted enrollments must never be picked as "the" enrollment.
        $e2Live = ($e['soft_deletes'] ?? false) ? ' and e2.deleted_at is null' : '';

        $inv = 'COALESCE(inv.invoiced, 0)';
        $pay = 'COALESCE(pay.paid, 0)';

        return DB::table("{$s['table']} as s")
            ->join("{$e['table']} as e", "e.{$e['student_fk']}", '=', 's.id')
            ->join("{$g['table']} as g", 'g.id', '=', "e.{$e['grade_fk']}")
            ->leftJoin("{$st['table']} as st", 'st.id', '=', "e.{$e['stream_fk']}")
            ->leftJoinSub($invoiced, 'inv', 'inv.student_id', '=', 's.id')
            ->leftJoinSub($paid, 'pay', 'pay.student_id', '=', 's.id')
            // Exactly ONE enrollment per student (the latest active one), so a
            // student with several enrollment rows is never listed twice.
            ->whereRaw(
                "e.id = (select e2.id from {$e['table']} e2
                          where e2.{$e['student_fk']} = s.id and e2.{$e['status_col']} = ?{$e2Live}
                          order by e2.created_at desc, e2.id desc limit 1)",
                [$e['active_value']]
            )
            ->when($s['soft_deletes'], fn ($q) => $q->whereNull('s.deleted_at'))
            ->when($s['status_col'], fn ($q, $col) => $q->where("s.{$col}", $s['active_value']))
            ->selectRaw("
                s.id as student_id,
                s.{$s['admission_no']} as admission_no,
                {$s['name_sql']} as student_name,
                g.id as grade_id,
                g.{$g['name']} as grade_name,
                g.{$g['sort']} as grade_sort,
                st.id as stream_id,
                COALESCE(st.{$st['name']}, '—') as stream_name,
                {$inv} as invoiced,
                {$pay} as paid,
                ({$inv} - {$pay}) as balance,
                CASE WHEN {$inv} > 0 THEN ROUND(({$inv} - {$pay}) / {$inv} * 100, 1) ELSE 0 END as balance_pct
            ");
    }

    /* ------------------------------------------------------------------ */
    /*  Summary & breakdown (screen)                                      */
    /* ------------------------------------------------------------------ */

    public function summary(array $f): object
    {
        $r = $this->query($f)->selectRaw('
            COUNT(*) as debtors,
            COALESCE(SUM(t.invoiced), 0) as invoiced,
            COALESCE(SUM(t.paid), 0) as paid,
            COALESCE(SUM(t.balance), 0) as balance
        ')->first();

        $r->pct = $r->invoiced > 0 ? round($r->balance / $r->invoiced * 100, 1) : 0;

        return $r;
    }

    /** Per-grade (default) or per-stream totals. */
    public function breakdown(array $f): Collection
    {
        $byStream = $f['group_by'] === 'stream';
        $cols = $byStream
            ? ['t.grade_id', 't.grade_name', 't.grade_sort', 't.stream_id', 't.stream_name']
            : ['t.grade_id', 't.grade_name', 't.grade_sort'];

        return $this->query($f)
            ->select($cols)
            ->selectRaw('COUNT(*) as debtors, SUM(t.invoiced) as invoiced, SUM(t.paid) as paid, SUM(t.balance) as balance')
            ->groupBy($cols)
            ->orderByRaw('LENGTH(t.grade_sort), t.grade_sort')
            ->when($byStream, fn ($q) => $q->orderBy('t.stream_name'))
            ->get()
            ->map(function ($r) use ($byStream) {
                $r->label = $byStream ? "{$r->grade_name} {$r->stream_name}" : $r->grade_name;
                $r->pct = $r->invoiced > 0 ? round($r->balance / $r->invoiced * 100, 1) : 0;

                return $r;
            });
    }

    /* ------------------------------------------------------------------ */
    /*  Export data                                                       */
    /* ------------------------------------------------------------------ */

    /** @return Collection<int, array{label: ?string, rows: Collection, totals: array}> */
    public function groups(array $f): Collection
    {
        $q = $this->query($f);

        if ($f['group_by'] !== 'none') {
            $q->orderByRaw('LENGTH(t.grade_sort), t.grade_sort');
        }
        if ($f['group_by'] === 'stream') {
            $q->orderBy('t.stream_name');
        }

        $rows = $q->orderByDesc('t.balance')->orderBy('t.student_name')->get();

        if ($f['group_by'] === 'none') {
            return collect([['label' => null, 'rows' => $rows, 'totals' => $this->totals($rows)]]);
        }

        return $rows
            ->groupBy(fn ($r) => $f['group_by'] === 'grade' ? $r->grade_id : $r->grade_id . '|' . $r->stream_id)
            ->map(function ($g) use ($f) {
                $first = $g->first();

                return [
                    'label'  => $f['group_by'] === 'grade' ? $first->grade_name : "{$first->grade_name} {$first->stream_name}",
                    'rows'   => $g->values(),
                    'totals' => $this->totals($g),
                ];
            })
            ->values();
    }

    public function totals(Collection $rows): array
    {
        $inv = (float) $rows->sum('invoiced');
        $paid = (float) $rows->sum('paid');
        $bal = (float) $rows->sum('balance');

        return [
            'count'    => $rows->count(),
            'invoiced' => $inv,
            'paid'     => $paid,
            'balance'  => $bal,
            'pct'      => $inv > 0 ? round($bal / $inv * 100, 1) : 0,
        ];
    }

    /** Human-readable filter line printed on both exports. */
    public function describe(array $f): string
    {
        $c = $this->c;
        $out = [];

        if ($f['grade_ids']) {
            $out[] = 'Grade: ' . DB::table($c['grades']['table'])
                    ->whereIn('id', $f['grade_ids'])->pluck($c['grades']['name'])->implode(', ');
        }
        if ($f['stream_ids']) {
            $out[] = 'Stream: ' . DB::table($c['streams']['table'])
                    ->whereIn('id', $f['stream_ids'])->pluck($c['streams']['name'])->unique()->implode(', ');
        }
        if ($f['min_pct'] !== null || $f['max_pct'] !== null) {
            $out[] = match (true) {
                $f['min_pct'] !== null && $f['max_pct'] !== null => $f['min_pct'] == $f['max_pct']
                    ? "Balance = {$f['min_pct']}% of fees"
                    : "Balance {$f['min_pct']}%–{$f['max_pct']}% of fees",
                $f['min_pct'] !== null => "Balance ≥ {$f['min_pct']}% of fees",
                default                => "Balance ≤ {$f['max_pct']}% of fees",
            };
        }
        if ($f['min_balance'] !== null) {
            $out[] = "Min balance: {$c['currency']} " . number_format($f['min_balance'], 2);
        }
        if ($f['q'] !== '') {
            $out[] = "Search: \"{$f['q']}\"";
        }
        if ($f['group_by'] !== 'none') {
            $out[] = 'Grouped by ' . $f['group_by'];
        }

        return $out ? implode('  |  ', $out) : 'All debtors';
    }

    /* ------------------------------------------------------------------ */
    /*  Filter dropdown data                                              */
    /* ------------------------------------------------------------------ */

    public function filterOptions(): array
    {
        $g = $this->c['grades'];
        $s = $this->c['streams'];

        $grades = DB::table($g['table'])
            ->select('id', "{$g['name']} as name")
            ->when($g['soft_deletes'] ?? false, fn ($q) => $q->whereNull('deleted_at'))
            ->orderByRaw("LENGTH({$g['sort']}), {$g['sort']}")
            ->get();

        $gradeNames = $grades->pluck('name', 'id');

        $streams = DB::table($s['table'])
            ->select('id', "{$s['name']} as name")
            ->when($s['soft_deletes'] ?? false, fn ($q) => $q->whereNull('deleted_at'))
            ->when($s['grade_fk'], fn ($q, $fk) => $q->addSelect("{$fk} as grade_id"))
            ->get()
            ->map(fn ($x) => [
                'id'       => (string) $x->id,
                'grade_id' => isset($x->grade_id) ? (string) $x->grade_id : null,
                'label'    => trim($gradeNames->get($x->grade_id ?? '', '') . ' ' . $x->name),
            ])
            ->sortBy('label', SORT_NATURAL)
            ->values();

        return [
            'grades'  => $grades->map(fn ($x) => ['id' => (string) $x->id, 'name' => $x->name])->values(),
            'streams' => $streams,
        ];
    }
}
