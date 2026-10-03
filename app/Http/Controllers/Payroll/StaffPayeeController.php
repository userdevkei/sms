<?php

namespace App\Http\Controllers\Payroll;

use App\Http\Controllers\Controller;
use App\Models\StaffPayee;
use App\Models\StaffPayItem;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class StaffPayeeController extends Controller
{
    public function index()
    {
        $payees = StaffPayee::with('user')->withCount('items')->orderBy('full_name')->get();

        return view('payroll.payees.index', compact('payees'));
    }

    public function create()
    {
        return view('payroll.payees.form', ['payee' => new StaffPayee(['is_active' => true, 'employment_type' => 'permanent', 'payment_method' => 'bank'])]);
    }

    public function store(Request $request)
    {
        DB::transaction(function () use ($request) {
            $payee = StaffPayee::create($this->validated($request));
            $this->syncItems($payee, $request);
        });

        return redirect()->route('payroll.payees.index')->with('success', 'Staff payee added.');
    }

    public function edit(StaffPayee $payee)
    {
        $payee->load('items');

        return view('payroll.payees.form', compact('payee'));
    }

    public function update(Request $request, StaffPayee $payee)
    {
        DB::transaction(function () use ($request, $payee) {
            $payee->update($this->validated($request, $payee));
            $this->syncItems($payee, $request);
        });

        return redirect()->route('payroll.payees.index')->with('success', 'Staff payee updated.');
    }

    public function destroy(StaffPayee $payee)
    {
        $payee->delete(); // past schedules keep their snapshot lines

        return back()->with('success', 'Staff payee removed.');
    }


    /** Select2 AJAX: non-student users who are not already payees. */
    public function users(Request $request)
    {
        $q = trim((string) $request->get('q', ''));
        $cols = array_values(array_intersect(
            ['name', 'first_name', 'middle_name', 'last_name', 'email', 'userID', 'phone', 'phone_number'],
            Schema::getColumnListing('users')
        ));

        $users = $this->staffCandidates()
            ->when($q !== '' && $cols, fn ($w) => $w->where(function ($x) use ($cols, $q) {
                foreach ($cols as $c) {
                    $x->orWhere($c, 'like', "%{$q}%");
                }
            }))
            ->limit(30)->get();

        return response()->json(['results' => $users->map(fn ($u) => $this->userPayload($u))->values()]);
    }

    public function importForm()
    {
        $users = $this->staffCandidates()->limit(500)->get()->map(fn ($u) => $this->userPayload($u));

        return view('payroll.payees.import', compact('users'));
    }

    /** Bulk-create payees from users. Created INACTIVE until a basic salary is set. */
    public function importStore(Request $request)
    {
        $ids = (array) $request->validate(['user_ids' => 'required|array|min:1', 'user_ids.*' => 'string|exists:users,id'])['user_ids'];

        $created = 0;
        DB::transaction(function () use ($ids, &$created) {
            foreach ($this->staffCandidates()->whereIn('id', $ids)->get() as $u) {
                $p = $this->userPayload($u);
                $staffNo = $p['staff_no'] && ! StaffPayee::where('staff_no', $p['staff_no'])->exists() ? $p['staff_no'] : null;
                StaffPayee::create([
                    'user_id'      => $u->id,
                    'full_name'    => $p['name'] ?: 'Unnamed user',
                    'staff_no'     => $staffNo,
                    'mpesa_phone'  => $p['phone'],
                    'basic_salary' => 0,
                    'is_active'    => false,
                ]);
                $created++;
            }
        });

        return redirect()->route('payroll.payees.index')
            ->with('success', "{$created} staff imported as inactive. Open each, set basic salary, payment details and activate.");
    }

    /** Users that are not students and not already linked to a payee. */
    private function staffCandidates()
    {
        $linked = StaffPayee::whereNotNull('user_id')->pluck('user_id');

        return User::query()
            ->whereNotIn('id', $linked)
            ->whereNotExists(fn ($s) => $s->select(DB::raw(1))->from('student_enrollments')
                ->whereColumn('student_enrollments.user_id', 'users.id')
                ->whereNull('student_enrollments.deleted_at'))
            ->orderBy(Schema::hasColumn('users', 'name') ? 'name' : 'id');
    }

    private function userPayload($u): array
    {
        $name = trim((string) ($u->name ?? '')) ?: collect([$u->first_name ?? null, $u->middle_name ?? null, $u->last_name ?? null])->filter()->implode(' ');

        return [
            'id'       => $u->id,
            'name'     => $name,
            'text'     => trim($name . ' ' . ($u->userID ?? '' ? '(' . $u->userID . ')' : '') . ' ' . ($u->email ?? '')),
            'staff_no' => $u->userID ?? null,
            'phone'    => $u->phone ?? $u->phone_number ?? null,
        ];
    }

    private function validated(Request $r, ?StaffPayee $payee = null): array
    {
        // Drop fully blank rows (clicked "+ Add line" and left it empty); keep partial ones so they get validated
        $r->merge(['items' => collect($r->input('items', []))
            ->filter(fn ($i) => filled($i['name'] ?? null) || filled($i['amount'] ?? null))
            ->values()->all()]);

        $data = $r->validate([
            'full_name'       => 'required_without:user_id|nullable|string|max:255',
            'staff_no'        => 'nullable|string|max:30|unique:staff_payees,staff_no,' . ($payee?->id ?? 'NULL') . ',id',
            'user_id'         => ['nullable', 'string', 'max:12', 'exists:users,id', Rule::unique('staff_payees', 'user_id')->ignore($payee?->id)->whereNull('deleted_at')],
            'id_number'       => 'nullable|string|max:30',
            'kra_pin'         => ['nullable', 'regex:/^[AP]\d{9}[A-Z]$/i'],
            'nssf_no'         => 'nullable|string|max:30',
            'shif_no'         => 'nullable|string|max:30',
            'designation'     => 'nullable|string|max:255',
            'department'      => 'nullable|string|max:255',
            'employment_type' => 'required|in:permanent,contract,casual',
            'start_date'      => 'nullable|date',
            'end_date'        => 'nullable|date|after_or_equal:start_date',
            'basic_salary'    => 'required|numeric|min:0',
            'payment_method'  => 'required|in:bank,mpesa,cash',
            'bank_name'       => 'nullable|string|max:255',
            'bank_branch'     => 'nullable|string|max:255',
            'bank_account'    => 'nullable|string|max:40',
            'mpesa_phone'     => ['nullable', 'regex:/^(?:\+?254|0)[17]\d{8}$/'],
            'items'           => 'nullable|array',
            'items.*.kind'    => ['required', Rule::in(array_keys(StaffPayItem::KINDS))],
            'items.*.name'    => 'required|string|max:255',
            'items.*.amount'  => 'required|numeric|min:0.01',
        ], [
            'user_id.unique'       => 'That user is already a staff payee.',
            'kra_pin.regex'        => 'KRA PIN must look like A123456789Z.',
            'mpesa_phone.regex'    => 'Enter a valid Kenyan phone number (07xx / 01xx / +254…).',
            'items.*.name.required'   => 'Every allowance/deduction line needs a description.',
            'items.*.amount.required' => 'Every allowance/deduction line needs an amount.',
            'items.*.amount.min'      => 'Allowance/deduction amounts must be greater than 0.',
        ]);

        unset($data['items']); // handled by syncItems(); must not reach StaffPayee::create()

        if (blank($data['full_name'] ?? null) && ! empty($data['user_id'])) {
            $data['full_name'] = $this->userPayload(User::find($data['user_id']))['name'] ?: 'Unnamed user';
        }
        $data['user_id']   = $data['user_id'] ?? null;
        $data['kra_pin']   = isset($data['kra_pin']) ? strtoupper($data['kra_pin']) : null;
        $data['is_active'] = $r->boolean('is_active');

        return $data;
    }

    private function syncItems(StaffPayee $payee, Request $r): void
    {
        $payee->items()->forceDelete();

        foreach ($r->input('items', []) as $i) {
            $payee->items()->create([
                'kind'      => $i['kind'],
                'name'      => $i['name'],
                'amount'    => $i['amount'],
                'is_active' => true,
            ]);
        }
    }

    public function export(Request $request)
    {
        $payees = $this->filteredPayees($request)->get();

        $book  = new Spreadsheet();
        $sheet = $book->getActiveSheet()->setTitle('Staff Payees');

        $applied = collect([
            'status'  => $request->status,
            'source'  => $request->source,
            'type'    => $request->type,
            'pay via' => $request->method,
            'KRA PIN' => $request->kra,
            'search'  => $request->q,
        ])->filter()->map(fn ($v, $k) => "{$k}: {$v}")->implode(' | ');

        // Title block (merged across the full table width)
        $sheet->setCellValue('A1', 'Staff Payees');
        $sheet->setCellValue('A2', 'Exported ' . now()->format('d M Y H:i') . ($applied ? "  |  Filters – {$applied}" : '  |  No filters'));
        $sheet->mergeCells('A1:T1');
        $sheet->mergeCells('A2:T2');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A2')->getFont()->setItalic(true)->getColor()->setRGB('555555');
        $sheet->getStyle('A1:A2')->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_LEFT)
            ->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension(1)->setRowHeight(24);

        // Header row
        $head = ['#', 'Staff No', 'Name', 'Designation', 'Department', 'Type', 'Source', 'ID No', 'KRA PIN', 'NSSF No',
            'SHIF No', 'Start Date', 'End Date', 'Basic Salary', 'Pay Via', 'Bank', 'Branch', 'Account No', 'M-Pesa', 'Status'];
        $sheet->fromArray($head, null, 'A4');

        // Data rows
        $r = 5;
        foreach ($payees as $i => $p) {
            $sheet->fromArray([
                $i + 1,
                $p->staff_no,
                $p->full_name,
                $p->designation,
                $p->department,
                ucfirst($p->employment_type),
                $p->user_id ? 'System user' : 'Standalone',
                $p->id_number,
                $p->kra_pin,
                $p->nssf_no,
                $p->shif_no,
                optional($p->start_date)->format('Y-m-d'),
                optional($p->end_date)->format('Y-m-d'),
                (float) $p->basic_salary,
                strtoupper($p->payment_method),
                $p->bank_name,
                $p->bank_branch,
                $p->bank_account,
                $p->mpesa_phone,
                $p->is_active ? 'Active' : 'Inactive',
            ], null, "A{$r}");

            // keep long numeric IDs / accounts / phones as text so Excel doesn't mangle them
            foreach (['H', 'J', 'K', 'R', 'S'] as $col) {
                $sheet->setCellValueExplicit(
                    "{$col}{$r}",
                    (string) $sheet->getCell("{$col}{$r}")->getValue(),
                    DataType::TYPE_STRING
                );
            }
            $r++;
        }

        // Total row
        $last = max($r - 1, 5);
        $sheet->setCellValue("M{$r}", 'TOTAL');
        $sheet->setCellValue("N{$r}", "=SUM(N5:N{$last})");
        $sheet->getStyle("N5:N{$r}")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
        $sheet->getStyle("M{$r}:N{$r}")->getFont()->setBold(true);

        // Header styling, filter, freeze
        $sheet->getStyle('A4:T4')->getFont()->setBold(true);
        $sheet->getStyle('A4:T4')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E9ECEF');
        $sheet->setAutoFilter("A4:T{$last}");
        $sheet->freezePane('D5');

        // Column widths: A fixed + centred, the rest auto
        foreach (range('B', 'T') as $c) {
            $sheet->getColumnDimension($c)->setAutoSize(true);
        }
        $sheet->getColumnDimension('A')->setWidth(6);
        $sheet->getStyle("A4:A{$last}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        return response()->streamDownload(function () use ($book) {
            (new Xlsx($book))->save('php://output');
        }, 'staff-payees-' . now()->format('Y-m-d') . '.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    private function filteredPayees(Request $r)
    {
        $q = trim((string) $r->q);

        return StaffPayee::query()
            ->when(in_array($r->status, ['active', 'inactive'], true), fn ($x) => $x->where('is_active', $r->status === 'active'))
            ->when($r->source === 'user', fn ($x) => $x->whereNotNull('user_id'))
            ->when($r->source === 'standalone', fn ($x) => $x->whereNull('user_id'))
            ->when(in_array($r->type, ['permanent', 'contract', 'casual'], true), fn ($x) => $x->where('employment_type', $r->type))
            ->when(in_array($r->method, ['bank', 'mpesa', 'cash'], true), fn ($x) => $x->where('payment_method', $r->method))
            ->when($r->kra === 'missing', fn ($x) => $x->where(fn ($w) => $w->whereNull('kra_pin')->orWhere('kra_pin', '')))
            ->when($r->kra === 'provided', fn ($x) => $x->whereNotNull('kra_pin')->where('kra_pin', '!=', ''))
            ->when($q !== '', fn ($x) => $x->where(fn ($w) => $w
                ->where('full_name', 'like', "%{$q}%")->orWhere('staff_no', 'like', "%{$q}%")
                ->orWhere('designation', 'like', "%{$q}%")->orWhere('department', 'like', "%{$q}%")
                ->orWhere('kra_pin', 'like', "%{$q}%")))
            ->orderBy('full_name');
    }
}
