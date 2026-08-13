<?php

namespace App\Http\Controllers;

use App\Models\AcademicTerm;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\ProgressionException;
use App\Models\RoomAllocation;
use App\Models\RoomReservation;
use App\Models\Setting;
use App\Models\Stream;
use App\Models\StudentEnrollment;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DashboardController extends Controller
{
    public function dashboard(Request $request)
    {
        $user      = auth()->user();
        $roleSlugs = $user->roles()->pluck('slug')->toArray(); // ASSUMPTION: User::roles() belongsToMany(Role::class, 'role_users')

        $currentTerm = $this->resolveCurrentTerm();

        $viewData = [
            'roleSlugs'   => $roleSlugs,
            'currentTerm' => $currentTerm,
        ];

        if (array_intersect($roleSlugs, ['super_admin', 'admin'])) {
            $viewData += $this->leadershipData($currentTerm);
        }

        if (array_intersect($roleSlugs, ['finance_officer', 'accountant'])) {
            $viewData += $this->financeData($currentTerm);
        }

        if (array_intersect($roleSlugs, ['class_teacher', 'teacher', 'exam_coodrinator'])) {
            $viewData += $this->academicStaffData($user);
        }

        if (in_array('registrar', $roleSlugs)) {
            $viewData += $this->registrarData($currentTerm);
        }

        if (in_array('hr_officer', $roleSlugs)) {
            $viewData += $this->hrData();
        }

        if (in_array('hostel_warden', $roleSlugs)) {
            $viewData += $this->hostelData();
        }

        if (in_array('driver', $roleSlugs)) {
            $viewData += $this->driverData($user);
        }

        if (in_array('parent', $roleSlugs)) {
            $viewData += $this->parentData($user);
        }

        if (in_array('student', $roleSlugs)) {
            $viewData += $this->studentData($user);
        }

        return view('admin.dashboard', $viewData);
    }

    protected function resolveCurrentTerm(): ?AcademicTerm
    {
        return AcademicTerm::whereDate('start_date', '<=', now())
            ->whereDate('end_date', '>=', now())
            ->first()
            ?? AcademicTerm::whereDate('start_date', '<=', now())
                ->orderByDesc('start_date')
                ->first();
    }

    protected function activeStudentIds()
    {
        return StudentEnrollment::where('status', 'active')->pluck('user_id')->unique();
    }

    /**
     * Payments recorded within a term's calendar window.
     *
     * Payments are NOT pegged to an invoice or a term — a student can pay
     * against their account at any time, for any reason. "Collected this
     * term" is therefore an approximation based on WHEN the cash came in
     * (payments.paid_on falling inside the term's start/end dates), not
     * which invoice or term it was meant to settle. This is the standard
     * way to read cash collection when payments aren't earmarked.
     */
    protected function paymentsForTerm(AcademicTerm $term)
    {
        return Payment::whereBetween('paid_on', [
            $term->start_date->format('Y-m-d'),
            $term->end_date->format('Y-m-d'),
        ]);
    }

    /* =========================================================
     |  BALANCE HELPERS
     |
     |  Payments are NOT tied to a single invoice (payments.invoice_id is
     |  nullable) — students pay against their account, not a specific bill.
     |  So "outstanding" is a running total: sum(invoices.total_amount) minus
     |  sum(payments.amount) for that student, across all their invoices/payments.
     |
     |  invoices.total_amount already has approved exemptions netted in
     |  (they're written as negative invoice_items rows when the invoice is
     |  generated) — do NOT subtract the exemptions table again here.
     |
     |  Result is signed: positive = still owed, negative = credit/overpayment.
     |========================================================= */

    /**
     * Signed net balance for a single student.
     */
    protected function studentBalance(string $userId): array
    {
        $invoiced = (float) Invoice::where('user_id', $userId)->sum('total_amount');
        $paid     = (float) Payment::where('user_id', $userId)->sum('amount');
        $balance  = $invoiced - $paid;

        return [
            'invoiced'    => $invoiced,
            'paid'        => $paid,
            'balance'     => $balance,          // signed
            'outstanding' => max($balance, 0),  // 0 if in credit
            'credit'      => max(-$balance, 0), // 0 if owed
        ];
    }

    /**
     * School-wide (or scoped) split of outstanding vs credit, computed per
     * student then aggregated — never nets one student's credit against
     * another student's debt.
     */
    protected function schoolWideBalances(?\Illuminate\Support\Collection $userIds = null): array
    {
        $invoicedSub = DB::table('invoices')->select('user_id', DB::raw('sum(total_amount) as invoiced'))->groupBy('user_id');
        $paidSub     = DB::table('payments')->select('user_id', DB::raw('sum(amount) as paid'))->groupBy('user_id');

        $balances = DB::table('users')
            ->leftJoinSub($invoicedSub, 'inv', 'inv.user_id', '=', 'users.id')
            ->leftJoinSub($paidSub, 'pay', 'pay.user_id', '=', 'users.id')
            ->when($userIds, fn ($q) => $q->whereIn('users.id', $userIds))
            ->select(DB::raw('coalesce(inv.invoiced,0) - coalesce(pay.paid,0) as balance'))
            ->pluck('balance');

        return [
            'outstanding' => (float) $balances->filter(fn ($b) => $b > 0)->sum(),
            'credit'      => (float) $balances->filter(fn ($b) => $b < 0)->sum(fn ($b) => abs($b)),
        ];
    }

    /**
     * Outstanding + credit split grouped by each student's CURRENT grade
     * (from their active enrollment) — invoices/payments aren't reliably
     * attributable to a single grade over time, so we bucket by grade-now.
     */
    protected function balancesByGrade(): array
    {
        $invoicedSub = DB::table('invoices')->select('user_id', DB::raw('sum(total_amount) as invoiced'))->groupBy('user_id');
        $paidSub     = DB::table('payments')->select('user_id', DB::raw('sum(amount) as paid'))->groupBy('user_id');

        $rows = DB::table('student_enrollments')
            ->where('student_enrollments.status', 'active')
            ->join('grade_levels', 'grade_levels.id', '=', 'student_enrollments.grade_level_id')
            ->leftJoinSub($invoicedSub, 'inv', 'inv.user_id', '=', 'student_enrollments.user_id')
            ->leftJoinSub($paidSub, 'pay', 'pay.user_id', '=', 'student_enrollments.user_id')
            ->select(
                'grade_levels.name',
                'grade_levels.sequence',
                DB::raw('coalesce(inv.invoiced,0) - coalesce(pay.paid,0) as balance')
            )
            ->orderBy('grade_levels.sequence')
            ->get();

        $grouped = $rows->groupBy('name');

        return [
            'outstanding' => $grouped->map(fn ($g) => (float) $g->sum(fn ($r) => max($r->balance, 0))),
            'credit'      => $grouped->map(fn ($g) => (float) $g->sum(fn ($r) => max(-$r->balance, 0))),
        ];
    }

    /* =========================================================
     |  LEADERSHIP  (super_admin, admin)
     |========================================================= */
    protected function leadershipData(?AcademicTerm $term): array
    {
        $activeIds = $this->activeStudentIds();

        $totalStudents = $activeIds->count();

        $totalStaff = User::whereHas('roles', fn ($q) => $q->whereNotIn('slug', ['student', 'parent']))
            ->where('status', 'active')
            ->count();

        $balances = $this->schoolWideBalances($activeIds);

        $collectedThisTerm = $term
            ? $this->paymentsForTerm($term)->sum('amount')
            : Payment::whereMonth('paid_on', now()->month)->sum('amount');

        $genderSplit = User::whereIn('id', $activeIds)
            ->select('gender', DB::raw('count(*) as total'))
            ->groupBy('gender')->pluck('total', 'gender');

        $byGrade = StudentEnrollment::where('student_enrollments.status', 'active')
            ->join('grade_levels', 'grade_levels.id', '=', 'student_enrollments.grade_level_id')
            ->select('grade_levels.name', DB::raw('count(*) as total'))
            ->groupBy('grade_levels.name', 'grade_levels.sequence')
            ->orderBy('grade_levels.sequence')
            ->pluck('total', 'name');

        $byCounty = User::whereIn('id', $activeIds)
            ->whereNotNull('county')
            ->select('county', DB::raw('count(*) as total'))
            ->groupBy('county')->orderByDesc('total')->limit(6)
            ->pluck('total', 'county');

        $collectionsTrend = AcademicTerm::orderByDesc('start_date')->limit(6)->get()->reverse()
            ->mapWithKeys(function ($t) {
                $label = "{$t->academic_year} T{$t->term_number}";
                $total = $this->paymentsForTerm($t)->sum('amount');
                return [$label => (float) $total];
            });

        $byMethod = Payment::select('method', DB::raw('sum(amount) as total'))
            ->groupBy('method')->pluck('total', 'method');

        return [
            'adminStats' => [
                'total_students'      => $totalStudents,
                'total_staff'         => $totalStaff,
                'outstanding_balance' => $balances['outstanding'],
                'credit_balance'      => $balances['credit'],
                'collected_this_term' => $collectedThisTerm,
            ],
            'adminCharts' => [
                'gender_split'      => $genderSplit,
                'by_grade'          => $byGrade,
                'by_county'         => $byCounty,
                'collections_trend' => $collectionsTrend,
                'by_method'         => $byMethod,
            ],
        ];
    }

    /* =========================================================
     |  FINANCE  (finance_officer, accountant)
     |========================================================= */
    protected function financeData(?AcademicTerm $term): array
    {
        $invoicedThisTerm = $term
            ? Invoice::where('academic_year', $term->academic_year)->where('term', $term->term_number)->sum('total_amount')
            : Invoice::sum('total_amount');

        $collectedThisTerm = $term
            ? $this->paymentsForTerm($term)->sum('amount')
            : Payment::sum('amount');

        $balances       = $this->schoolWideBalances();
        $collectionRate = $invoicedThisTerm > 0 ? round(($collectedThisTerm / $invoicedThisTerm) * 100, 1) : 0;

        $invoiceStatus = Invoice::select('status', DB::raw('count(*) as total'))
            ->groupBy('status')->pluck('total', 'status');

        $byMethod = Payment::select('method', DB::raw('sum(amount) as total'))
            ->groupBy('method')->pluck('total', 'method');

        $collectionsTrend = AcademicTerm::orderByDesc('start_date')->limit(6)->get()->reverse()
            ->mapWithKeys(function ($t) {
                $label = "{$t->academic_year} T{$t->term_number}";
                return [$label => (float) $this->paymentsForTerm($t)->sum('amount')];
            });

        $byGrade = $this->balancesByGrade();

        return [
            'financeStats' => [
                'invoiced_this_term'  => $invoicedThisTerm,
                'collected_this_term' => $collectedThisTerm,
                'outstanding_balance' => $balances['outstanding'],
                'credit_balance'      => $balances['credit'],
                'collection_rate'     => $collectionRate,
            ],
            'financeCharts' => [
                'invoice_status'         => $invoiceStatus,
                'by_method'              => $byMethod,
                'collections_trend'      => $collectionsTrend,
                'balance_by_grade_labels'=> $byGrade['outstanding']->keys(),
                'outstanding_by_grade'   => $byGrade['outstanding']->values(),
                'credit_by_grade'        => $byGrade['credit']->values(),
            ],
        ];
    }

    /* =========================================================
     |  ACADEMIC STAFF  (class_teacher, teacher, exam_coodrinator)
     |========================================================= */
    protected function academicStaffData(User $user): array
    {
        // Only class_teacher has an assigned stream; teacher/exam_coodrinator get
        // a school-wide read-only snapshot instead of "my class" data.
        $myStreams   = Stream::where('class_teacher_id', $user->id)->with('gradeLevel')->get();
        $myStreamIds = $myStreams->pluck('id');

        $myStudentsCount = StudentEnrollment::whereIn('stream_id', $myStreamIds)
            ->where('status', 'active')->count();

        $genderSplit = StudentEnrollment::whereIn('stream_id', $myStreamIds)
            ->where('status', 'active')
            ->join('users', 'users.id', '=', 'student_enrollments.user_id')
            ->select('users.gender', DB::raw('count(*) as total'))
            ->groupBy('users.gender')->pluck('total', 'gender');

        return [
            'academicStats' => [
                'my_streams'  => $myStreams->count(),
                'my_students' => $myStudentsCount,
            ],
            'academicCharts' => [
                'my_gender_split' => $genderSplit,
            ],
            'myStreams' => $myStreams,
        ];
    }

    /* =========================================================
     |  REGISTRAR
     |========================================================= */
    protected function registrarData(?AcademicTerm $term): array
    {
        $currentYear = $term->academic_year ?? now()->year;

        $totalThisYear = StudentEnrollment::where('academic_year', $currentYear)->count();

        $newThisTerm = StudentEnrollment::where('academic_year', $currentYear)
            ->when($term, fn ($q) => $q->whereDate('enrolled_on', '>=', $term->start_date))
            ->count();

        $pendingExceptions = ProgressionException::where('status', 'pending')->count();

        $byGrade = StudentEnrollment::where('academic_year', $currentYear)
            ->join('grade_levels', 'grade_levels.id', '=', 'student_enrollments.grade_level_id')
            ->select('grade_levels.name', DB::raw('count(*) as total'))
            ->groupBy('grade_levels.name', 'grade_levels.sequence')
            ->orderBy('grade_levels.sequence')->pluck('total', 'name');

        $exceptionsByType = ProgressionException::select('type', DB::raw('count(*) as total'))
            ->groupBy('type')->pluck('total', 'type');

        return [
            'registrarStats' => [
                'total_enrollments_year' => $totalThisYear,
                'new_this_term'          => $newThisTerm,
                'pending_exceptions'     => $pendingExceptions,
            ],
            'registrarCharts' => [
                'by_grade'           => $byGrade,
                'exceptions_by_type' => $exceptionsByType,
            ],
        ];
    }

    /* =========================================================
     |  HR OFFICER
     |========================================================= */
    protected function hrData(): array
    {
        $staffQuery = User::whereHas('roles', fn ($q) => $q->whereNotIn('slug', ['student', 'parent']))
            ->where('status', 'active');

        $total = (clone $staffQuery)->count();

        $byGender = (clone $staffQuery)->select('gender', DB::raw('count(*) as total'))
            ->groupBy('gender')->pluck('total', 'gender');

        $byRole = DB::table('role_users') // ASSUMPTION: pivot table name/columns
        ->join('roles', 'roles.id', '=', 'role_users.role_id')
            ->whereNotIn('roles.slug', ['student', 'parent'])
            ->select('roles.name', DB::raw('count(*) as total'))
            ->groupBy('roles.name')->pluck('total', 'name');

        return [
            'hrStats'  => ['total_staff' => $total],
            'hrCharts' => [
                'staff_gender'  => $byGender,
                'staff_by_role' => $byRole,
            ],
        ];
    }

    /* =========================================================
     |  HOSTEL WARDEN
     |========================================================= */
    protected function hostelData(): array
    {
        $activeAllocations   = RoomAllocation::where('status', 'active')->count();
        $pendingReservations = RoomReservation::where('status', 'pending')->count();

        // ASSUMPTION: a `rooms` table exists with a `hostel_id` FK (referenced by
        // room_allocations/room_reservations but its migration wasn't supplied).
        $occupancyByHostel = RoomAllocation::where('room_allocations.status', 'active')
            ->join('rooms', 'rooms.id', '=', 'room_allocations.room_id')
            ->join('hostels', 'hostels.id', '=', 'rooms.hostel_id')
            ->select('hostels.name', DB::raw('count(*) as total'))
            ->groupBy('hostels.name')->pluck('total', 'name');

        $reservationStatus = RoomReservation::select('status', DB::raw('count(*) as total'))
            ->groupBy('status')->pluck('total', 'status');

        return [
            'hostelStats' => [
                'active_allocations'   => $activeAllocations,
                'pending_reservations' => $pendingReservations,
            ],
            'hostelCharts' => [
                'occupancy_by_hostel' => $occupancyByHostel,
                'reservation_status'  => $reservationStatus,
            ],
        ];
    }

    /* =========================================================
     |  DRIVER
     |========================================================= */
    protected function driverData(User $user): array
    {
        // ASSUMPTION: no driver<->vehicle/route assignment table was supplied.
        // If vehicles has a `driver_id` column, swap the line below accordingly.
        $vehicle = Vehicle::where('id', $user->vehicle_id ?? null)->first();

        return [
            'driverStats' => [
                'vehicle' => $vehicle,
            ],
        ];
    }

    /* =========================================================
     |  PARENT
     |========================================================= */
    protected function parentData(User $user): array
    {
        // ASSUMPTION: guardians are linked to students via a `guardian_student`
        // pivot (guardian_id, user_id). Replace with your real table/relationship.
        $childIds = DB::table('guardian_student')->where('guardian_id', $user->id)->pluck('user_id');

        $children = User::whereIn('id', $childIds)->get();

        $balances = $this->schoolWideBalances($childIds);

        $balanceByChild = $children->mapWithKeys(function ($child) {
            $b = $this->studentBalance($child->id);
            return [$child->first_name ?? $child->id => $b['balance']]; // signed — colour-coded in JS
        });

        return [
            'parentStats' => [
                'children_count'    => $children->count(),
                'outstanding_total' => $balances['outstanding'],
                'credit_total'      => $balances['credit'],
            ],
            'parentCharts' => [
                'balance_by_child' => $balanceByChild,
            ],
            'children' => $children,
        ];
    }

    /* =========================================================
     |  STUDENT
     |========================================================= */
    protected function studentData(User $user): array
    {
        $enrollment = StudentEnrollment::where('user_id', $user->id)
            ->where('status', 'active')->latest()->first();

        $balance = $this->studentBalance($user->id);

        $lastPayment = Payment::where('user_id', $user->id)->latest('paid_on')->first();

        $nextDueInvoice = Invoice::where('user_id', $user->id)
            ->whereIn('status', ['unpaid', 'partially_paid'])
            ->whereNotNull('due_date')
            ->orderBy('due_date')
            ->first();

        return [
            'studentStats' => [
                'grade'            => $enrollment?->gradeLevel?->name,
                'stream'           => $enrollment?->stream?->name,
                'outstanding'      => $balance['outstanding'],
                'credit'           => $balance['credit'],
                'balance'          => $balance['balance'], // signed, use this to decide which badge to show
                'last_payment'     => $lastPayment,
                'next_due_invoice' => $nextDueInvoice,
            ],
        ];
    }
    public function settings()
    {
        $settings = Setting::pluck('value', 'key'); // unchanged

        return view('admin.settings', [
            'settings'        => $settings,
            'smsGateways'     => \App\Models\Gateway::where('type', 'sms')->with('credentials')->latest('created_at')->get(),
            'paymentGateways' => \App\Models\Gateway::where('type', 'payment')->with('credentials')->latest('created_at')->get(),
            'emailGateways'   => \App\Models\Gateway::where('type', 'email')->with('credentials')->latest('created_at')->get(),
        ]);
    }

    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'school_name'      => 'required|string',
            'tagline'          => 'nullable|string',
            'motto'            => 'nullable|string',
            'primary_color'    => 'nullable|string',
            'secondary_color'  => 'nullable|string',
            'sidebar_color'    => 'nullable|string',
            'address'          => 'nullable|string',
            'phone'            => 'nullable|string',
            'currency'         => 'nullable|string',
            'email'            => 'nullable|email',
            'logo'             => 'nullable|file|image|max:2048',
            'favicon'          => 'nullable|file|mimes:ico,png,svg|max:512',
        ]);

        foreach ($validated as $key => $value) {
            if (in_array($key, ['logo', 'favicon'], true)) {
                continue; // handled separately below
            }
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        if ($request->hasFile('logo')) {
            $this->storeBrandingFile($request->file('logo'), 'logo_path');
        }

        if ($request->hasFile('favicon')) {
            $this->storeBrandingFile($request->file('favicon'), 'favicon_path');
        }

        return redirect()->back()->with('success', 'Settings updated successfully');
    }

    /**
     * Store an uploaded branding file (logo/favicon) at Files/branding,
     * remove the previous file for that setting key, and persist the new path.
     */
    protected function storeBrandingFile(\Illuminate\Http\UploadedFile $file, string $settingKey): void
    {
        $filename = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME))
            . '-' . time()
            . '.' . $file->getClientOriginalExtension();

        $destination = base_path('Files/branding');

        if (! is_dir($destination)) {
            mkdir($destination, 0755, true);
        }

        $oldPath = Setting::where('key', $settingKey)->value('value');
        if ($oldPath && file_exists(base_path($oldPath))) {
            unlink(base_path($oldPath));
        }

        $file->move($destination, $filename);

        Setting::updateOrCreate(
            ['key' => $settingKey],
            ['value' => 'Files/branding/' . $filename]
        );
    }
}
