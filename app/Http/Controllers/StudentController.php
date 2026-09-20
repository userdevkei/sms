<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStudentRequest;
use App\Http\Requests\UpdateStudentRequest;
use App\Imports\UsersImport;
use App\Models\GradeLevel;
use App\Models\Invoice;
use App\Models\Pathway;
use App\Models\Role;
use App\Models\RoleUser;
use App\Models\Setting;
use App\Models\Stream;
use App\Models\StudentEnrollment;
use App\Models\User;
use App\Services\Common;
use App\Services\Common\FileUploadService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Mpdf\Mpdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class StudentController extends Controller
{
    /** Every query in this controller is scoped to users with the 'student' role. */
    private function studentScope($query)
    {
        return $query->whereExists(function ($sub) {
            $sub->select(DB::raw(1))
                ->from('role_users')
                ->join('roles', 'roles.id', '=', 'role_users.role_id')
                ->whereColumn('role_users.user_id', 'users.id')
                ->where('roles.slug', 'student');
        });
    }

    public function index()
    {
        $roles = Role::where('slug', 'student')->get();
        return view('students.index', compact('roles'));
    }

    public function data(Request $request): JsonResponse
    {
        $draw = (int) $request->input('draw', 1);
        $start = (int) $request->input('start', 0);
        $length = max(1, (int) $request->input('length', 25));
        $fullNameExpr = "TRIM(REPLACE(CONCAT(first_name, ' ', COALESCE(NULLIF(middle_name, ''), ''), ' ', last_name), '  ', ' '))";

        $columnMap = [1 => 'full_name', 2 => 'userID', 3 => 'email', 4 => 'phone_number', 6 => 'status', 7 => 'created_at'];
        $orderColumn = $columnMap[(int) $request->input('order.0.column', 7)] ?? 'created_at';
        $orderDir = $request->input('order.0.dir') === 'asc' ? 'asc' : 'desc';

        $baseQuery = $this->studentScope(
            DB::table('users')
                ->selectRaw("id, userID, first_name, middle_name, last_name, email, phone_number, status, avatar, gender, created_at, {$fullNameExpr} as full_name")
                ->whereNull('deleted_at')
        );

        $totalRecords = (clone $baseQuery)->count();

        if ($status = $request->input('filter_status')) {
            $baseQuery->where('status', $status);
        }
        if ($gender = $request->input('filter_gender')) {
            $baseQuery->where('gender', $gender);
        }
        if ($search = trim((string) $request->input('search.value'))) {
            $baseQuery->where(function ($q) use ($search, $fullNameExpr) {
                $q->whereRaw("{$fullNameExpr} LIKE ?", ["%{$search}%"])
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone_number', 'like', "%{$search}%")
                    ->orWhere('userID', 'like', "%{$search}%");
            });
        }

        $filteredRecords = (clone $baseQuery)->count();

        $students = $baseQuery->orderBy($orderColumn, $orderDir)->offset($start)->limit($length)->get();

        $data = $students->values()->map(function ($student, $index) use ($start) {
            $avatarUrl = $student->avatar
                ? route('file', ['path' => $student->avatar])
                : route('file', ['path' => 'Files/images/avatar.png']);

            return [
                'sn'          => $start + $index + 1,
                'avatar'      => $avatarUrl,
                'name'        => $student->full_name,
                'userID'      => $student->userID ?: '—',
                'email'       => $student->email,
                'phone'       => $student->phone_number ?: '—',
                'status'      => $student->status,
                'created_at'  => Carbon::parse($student->created_at)->format('d M Y'),
                'profile_url' => route('students.profile', $student->id),
                'edit_url'    => route('students.edit', $student->id),
                'delete_url'  => route('students.destroy', $student->id),
                'impersonate_url' => route('impersonate.start', $student->id),
            ];
        });

        return response()->json([
            'draw' => $draw, 'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords, 'data' => $data,
        ]);
    }

    public function create()
    {
        $roles = Role::where('slug', 'student')->get();
        $gradeLevels = GradeLevel::where('status', 'active')->orderBy('sequence', 'asc')->get();
        return view('students.create', compact('roles', 'gradeLevels'));
    }

    public function store(StoreStudentRequest $request)
    {
        $validated = $request->validated();

        if ($request->hasFile('avatar')) {
            $validated['avatar'] = FileUploadService::store($request->file('avatar'), 'Files/images');
        }
        $role = Role::where('slug', 'student')->first();
        $validated['password'] = Hash::make($validated['password']);
        $student = User::query()->create($validated);
        RoleUser::updateOrCreate([
            'user_id' => $student->id,
            'role_id' => $role->id,
        ]);
        $stream = Stream::where('id', $request->stream_id)->first();
        StudentEnrollment::updateOrCreate(
            [
                'user_id' => $student->id,
                'academic_year' => $request->academic_year,
            ],
            [
                'grade_level_id' => $request->grade_level_id,
                'stream_id' => $request->stream_id,
                'pathway_id' => $stream->pathway_id,
                'enrolled_on' => date('Y-m-d'),
            ]
        );

        $studentRole = Role::query()->where('slug', 'student')->firstOrFail();
        $student->roles()->sync([$studentRole->id]);

        return redirect()->route('students.index')->with('success', "{$student->full_name} was added successfully.");
    }

    public function edit(User $student)
    {
        abort_unless($student->roles->pluck('slug')->contains('student'), 404);
        $roles = Role::where('slug', 'student')->get();
        $currentEnrollment = $student->enrollments()->where('status', 'active')->latest('academic_year')->first() ?? null;
        $gradeLevels = GradeLevel::where('status', 'active')->orderBy('sequence', 'asc')->get();
        return view('students.edit', compact('student', 'roles', 'currentEnrollment', 'gradeLevels'));
    }

    public function update(UpdateStudentRequest $request, User $student)
    {
        $validated = $request->validated();

        $role = Role::where('slug', 'student')->first();
        if ($request->hasFile('avatar')) {
            FileUploadService::delete($student->avatar);
            $validated['avatar'] = FileUploadService::store($request->file('avatar'), 'Files/images');
        }

        if (! empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }


        $student->update($validated);

        RoleUser::updateOrCreate([
            'user_id' => $student->id,
            'role_id' => $role->id,
        ]);

        $stream = Stream::where('id', $request->stream_id)->first();
        StudentEnrollment::updateOrCreate(
            [
                'user_id' => $student->id,
                'academic_year' => $request->academic_year,
            ],
            [
                'grade_level_id' => $request->grade_level_id,
                'stream_id' => $request->stream_id,
                'pathway_id' => $stream?->pathway_id,
                'enrolled_on' => date('Y-m-d'),
            ]
        );

        return redirect()->route('students.index')->with('success', "{$student->full_name} was updated successfully.");
    }

    public function destroy(User $student): JsonResponse
    {
        $student->delete();

        return response()->json(['success' => true, 'message' => 'Student deleted successfully.']);
    }

    public function profile($id)
    {
        $student = User::query()->findOrFail($id);
        return view('students.profile', ['user' => $student]);
    }

    // ---- Import (moved here verbatim from UserController — it always
    //      hardcoded the 'student' role anyway, so this is where it belonged) ----

    public function import()
    {
        return view('students.import');
    }

    public function preview(Request $request)
    {
        $request->validate(['file' => 'required|file|mimes:csv,xlsx,xls|max:5120']);

        $rows = (new UsersImport)->toCollection($request->file('file'));

        $seenEmails = [];
        $seenUserIds = [];
        $preview = [];

        // Loaded once per request — name => [id, sequence], case-insensitive.
        $gradeLevels = GradeLevel::query()->get(['id', 'name', 'sequence', 'code'])
            ->mapWithKeys(fn ($g) => [mb_strtolower(trim($g->code)) => ['id' => $g->id, 'sequence' => $g->sequence]]);

        // Grade 10 and above (by sequence) require a pathway. Derived from the
        // actual G10 row rather than hardcoded, so renumbering doesn't silently break this.
        $pathwayThresholdSequence = GradeLevel::query()->where('code', 'G10')->value('sequence');

        $pathways = Pathway::query()->pluck('id', 'code')
            ->mapWithKeys(fn ($id, $code) => [mb_strtolower(trim($code)) => $id]);

        $existingStreams = Stream::query()->get(['id', 'grade_level_id', 'name'])
            ->mapWithKeys(fn ($s) => [$s->grade_level_id.'|'.mb_strtolower(trim($s->name)) => $s->id]);

        foreach ($rows as $index => $row) {
            $data = [
                'first_name'  => trim((string) ($row['first_name'] ?? '')),
                'last_name'   => trim((string) ($row['last_name'] ?? '')),
                'middle_name' => trim((string) ($row['middle_name'] ?? '')),
                'user_id'     => trim((string) ($row['user_id'] ?? '')),
                'email'       => strtolower(trim((string) ($row['email'] ?? ''))),
                'gender'      => strtolower(trim((string) ($row['gender'] ?? ''))),
                'phone'       => trim((string) ($row['phone'] ?? '')),
                'county'      => trim((string) ($row['county'] ?? '')),
                'sub_county'  => trim((string) ($row['sub_county'] ?? '')),
                'ward'        => trim((string) ($row['ward'] ?? '')),
                'grade'       => trim((string) ($row['grade'] ?? '')),
                'stream'      => trim((string) ($row['stream'] ?? '')),
                'pathway'     => trim((string) ($row['pathway'] ?? '')),
                'balance'     => trim((string) ($row['balance'] ?? '')),
            ];

            $errors = [];

            if ($data['first_name'] === '') $errors[] = 'First name is required';
            if ($data['last_name'] === '') $errors[] = 'Last name is required';

            if ($data['user_id'] === '') {
                $errors[] = 'Admission Number (User ID) is required';
            } elseif (isset($seenUserIds[$data['user_id']])) {
                $errors[] = 'Duplicate Admission Number within this file (row '.$seenUserIds[$data['user_id']].')';
            } elseif (User::where('userID', $data['user_id'])->exists()) {
                $errors[] = 'Admission Number already exists in the system';
            }

            if ($data['email'] === '' || ! filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'A valid email is required';
            } elseif (isset($seenEmails[$data['email']])) {
                $errors[] = 'Duplicate email within this file (row '.$seenEmails[$data['email']].')';
            } elseif (User::where('email', $data['email'])->exists()) {
                $errors[] = 'Email already exists in the system';
            }

            if (! in_array($data['gender'], ['male', 'female'], true)) {
                $errors[] = 'Gender must be male or female';
            }

            // Grade must match an existing GradeLevel by name — we never create grades on the fly.
            $gradeLevelId = null;
            $gradeSequence = null;
            if ($data['grade'] === '') {
                $errors[] = 'Grade is required';
            } else {
                $grade = $gradeLevels->get(mb_strtolower($data['grade']));
                if (! $grade) {
                    $errors[] = "Grade \"{$data['grade']}\" does not exist";
                } else {
                    $gradeLevelId = $grade['id'];
                    $gradeSequence = $grade['sequence'];
                }
            }

            $requiresPathway = $gradeSequence !== null && $gradeSequence >= $pathwayThresholdSequence;

            // Pathway: only meaningful (and only required) from G10 up.
            $pathwayId = null;
            if ($requiresPathway) {
                if ($data['pathway'] === '') {
                    $errors[] = 'Pathway is required from Grade 10 upward';
                } else {
                    $pathwayId = $pathways->get(mb_strtolower($data['pathway']));
                    if (! $pathwayId) {
                        $errors[] = "Pathway \"{$data['pathway']}\" does not exist";
                    }
                }
            }

            // Stream is optional; if it doesn't exist under this grade yet, flag it for creation
            // and reserve the key so repeated rows in the same file don't each claim "will create".
            $streamWillBeCreated = false;
            if ($data['stream'] !== '' && $gradeLevelId) {
                $key = $gradeLevelId.'|'.mb_strtolower($data['stream']);
                if (! $existingStreams->has($key)) {
                    $streamWillBeCreated = true;
                    $existingStreams->put($key, null);
                }
            }

            // Balance is optional and signed: positive = owed, negative = overpayment/credit.
            $balance = null;
            if ($data['balance'] !== '') {
                if (! is_numeric($data['balance'])) {
                    $errors[] = 'Balance must be a number';
                } else {
                    $balance = round((float) $data['balance'], 2);
                }
            }

            if ($data['user_id'] !== '') $seenUserIds[$data['user_id']] = $index + 2;
            if ($data['email'] !== '') $seenEmails[$data['email']] = $index + 2;

            $preview[] = [
                'row' => $index + 2,
                'data' => $data,
                'grade_level_id' => $gradeLevelId,
                'pathway_id' => $pathwayId,
                'stream_will_be_created' => $streamWillBeCreated,
                'balance' => $balance,
                'errors' => $errors,
                'valid' => empty($errors),
            ];
        }

        $token = (string) Str::uuid();
        Cache::put('student-import:'.$token, $preview, now()->addMinutes(20));

        return view('students.import-preview', [
            'token' => $token,
            'rows'  => $preview,
            'validCount'   => collect($preview)->where('valid', true)->count(),
            'invalidCount' => collect($preview)->where('valid', false)->count(),
        ]);
    }

    public function importUsers(Request $request)
    {
        $request->validate([
            'token'      => 'required|string',
            'selected'   => 'nullable|array',
            'selected.*' => 'integer',
        ]);

        $preview = Cache::get('student-import:'.$request->input('token'));

        if (! $preview) {
            return redirect()->route('students.import.create')
                ->with('error', 'This import session has expired. Please upload the file again.');
        }

        $selected = array_flip($request->input('selected', []));
        $imported = 0;
        $skipped = 0;
        $studentRole = Role::query()->where('slug', 'student')->firstOrFail();



        $academicYear = (new Common())->resolveCurrentTerm();

        // Streams created earlier in *this* run get reused instead of raced into existing twice.
        $createdStreams = [];

        // Seed the Opening Balance sequence from what already exists this year.
        // Not lock-protected — fine for one admin running one import; add
        // lockForUpdate() if two imports could ever run concurrently.
        $obSequence = Invoice::query()
            ->where('invoice_number', 'like', "OB-{$academicYear->academic_year}-%")
            ->count();

        DB::transaction(function () use (
            $preview, $selected, $studentRole, $academicYear,
            &$createdStreams, &$obSequence, &$imported, &$skipped
        ) {
            foreach ($preview as $i => $entry) {
                if (! isset($selected[$i]) || ! $entry['valid']) {
                    $skipped++;
                    continue;
                }

                $data = $entry['data'];

                $exists = User::where('userID', $data['user_id'])
                    ->orWhere('email', $data['email'])
                    ->exists();

                if ($exists) {
                    $skipped++;
                    continue;
                }

                $student = User::create([
                    'first_name'   => $data['first_name'],
                    'last_name'    => $data['last_name'],
                    'middle_name'  => $data['middle_name'] ?: null,
                    'userID'       => $data['user_id'],
                    'email'        => $data['email'],
                    'gender'       => $data['gender'],
                    'phone_number' => $data['phone'] ?: null,
                    'password'     => Hash::make($data['user_id']),
                    'county'       => $data['county'],
                    'sub_county'   => $data['sub_county'],
                    'ward'         => $data['ward'],
                    'status'       => 'active',
                ]);

                RoleUser::firstOrCreate(['user_id' => $student->id, 'role_id' => $studentRole->id]);

                // --- Grade / Stream / Enrollment ---
                $streamId = null;
                if ($entry['grade_level_id'] && $data['stream'] !== '') {
                    $streamKey = $entry['grade_level_id'].'|'.mb_strtolower($data['stream']);

                    if (isset($createdStreams[$streamKey])) {
                        $streamId = $createdStreams[$streamKey];
                    } else {
                        $stream = Stream::firstOrCreate(
                            ['grade_level_id' => $entry['grade_level_id'], 'name' => $data['stream']],
                            ['pathway_id' => $entry['pathway_id'] ?? null, 'class_teacher_id' => null]
                        );
                        $createdStreams[$streamKey] = $stream->id;
                        $streamId = $stream->id;
                    }
                }

                if ($entry['grade_level_id']) {
                    StudentEnrollment::create([
                        'user_id'        => $student->id,
                        'grade_level_id' => $entry['grade_level_id'],
                        'stream_id'      => $streamId,
                        'academic_year'  => $academicYear->academic_year,
                        'status'         => 'active',
                        'enrolled_on'    => now(),
                    ]);
                }

                // --- Opening balance as a manual adjustment invoice ---
                if ($entry['balance'] !== null && $entry['balance'] != 0) {
                    $balance = $entry['balance'];
                    $isCredit = $balance < 0;

                    $obSequence++;
                    $invoiceNumber = sprintf('OB-%s-%04d', $academicYear->academic_year, $obSequence);


                    // This is the JSON payload shape — kept as an array here since we're
                    // writing straight to Eloquent rather than an HTTP call.
                    $invoicePayload = [
                        'invoice_number' => $invoiceNumber,
                        'user_id'        => $student->id,
                        'grade_level_id' => $entry['grade_level_id'],
                        'academic_year'  => $academicYear->academic_year,
                        'term'           => $academicYear->term_number,
                        'total_amount'   => $balance,
                        'amount_paid'    => 0,
                        'balance'        => $balance,
                        'status'         => $isCredit ? 'paid' : 'unpaid',
                        'generated_by'   => auth()->id(),
                        'items' => [[
                            'description' => $isCredit ? 'Opening Balance (Credit)' : 'Opening Balance',
                            'quantity'    => 1,
                            'amount'      => $balance,
                        ]],
                    ];

                    $invoice = Invoice::create(collect($invoicePayload)->except('items')->all());
                    $invoice->items()->create($invoicePayload['items'][0]);
                }

                $imported++;
            }
        });

        Cache::forget('student-import:'.$request->input('token'));

        return redirect()->route('students.index')
            ->with('success', "Import complete: {$imported} student(s) created, {$skipped} skipped.");
    }

    public function template()
    {
        return response()->download(base_path('Files/template/StudentTemplate.xlsx'));
    }

    public function exportPdf(Request $request)
    {
        $students = $this->filteredExportRows($request);

        $html = view('students.export-pdf', [
            'students'    => $students,
            'filters'     => $this->activeFilterSummary($request),
            'generatedAt' => now()->format('d M Y, H:i'),
            'schoolName'  => setting('school_name', config('app.name')),
            'logoPath'    => $this->resolveImageBase64(setting('logo_path')),
            'faviconPath' => $this->resolveImageBase64(setting('favicon_path')),
        ])->render();

        $mpdf = new Mpdf([
            'format'        => 'A4-L',
            'margin_left'   => 10,
            'margin_right'  => 5,
            'margin_top'    => 5,
            'margin_bottom' => 10,
            'margin_header' => 8,
            'margin_footer' => 8,
        ]);

        $mpdf->SetHTMLFooter('
        <table style="width: 100%; font-size: 9px; color: #9ca3af;">
            <tr>
                <td style="text-align: left;">' . setting('motto', setting('school_name')) . '</td>
                <td style="text-align: right;">Page {PAGENO} of {nbpg}</td>
            </tr>
        </table>
    ');

        $mpdf->WriteHTML($html);

        return response($mpdf->Output('students-' . now()->format('Y-m-d-His') . '.pdf', 'S'))
            ->header('Content-Type', 'application/pdf');
    }

    public function exportExcel(Request $request)
    {
        $students = $this->filteredExportRows($request);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Students');

        $sheet->setCellValue('A1', setting('school_name', config('app.name')) . ' — Students');
        $sheet->mergeCells('A1:J1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        $sheet->setCellValue('A2', $this->activeFilterSummary($request));
        $sheet->mergeCells('A2:J2');
        $sheet->getStyle('A2')->getFont()->setItalic(true)->setSize(9)->getColor()->setRGB('666666');

        $sheet->setCellValue('A3', 'Generated: ' . now()->format('d M Y, H:i'));
        $sheet->mergeCells('A3:J3');
        $sheet->getStyle('A3')->getFont()->setSize(9)->getColor()->setRGB('999999');

        $headerRow = 5;
        $headers = ['#', 'Admission No.', 'Full Name', 'Gender', 'Email', 'Phone', 'County', 'Sub County', 'Ward', 'Status'];
        $sheet->fromArray($headers, null, "A{$headerRow}");
        $sheet->getStyle("A{$headerRow}:J{$headerRow}")->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $sheet->getStyle("A{$headerRow}:J{$headerRow}")->getFill()
            ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('0B3D62');
        $sheet->getStyle("A{$headerRow}:J{$headerRow}")->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

        $row = $headerRow + 1;
        foreach ($students as $index => $student) {
            $sheet->fromArray([
                $index + 1,
                $student->userID ?: '—',
                $student->full_name,
                $student->gender ? ucfirst($student->gender) : '—',
                $student->email,
                $student->phone_number ?: '—',
                $student->county ?: '—',
                $student->sub_county ?: '—',
                $student->ward ?: '—',
                ucfirst($student->status),
            ], null, "A{$row}");

            $row++;
        }

        foreach (range('A', 'J') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $filename = 'students-' . now()->format('Y-m-d-His') . '.xlsx';

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    private function resolveImageBase64(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        $fullPath = base_path($path);

        if (! file_exists($fullPath)) {
            \Log::info('Logo file not found', ['path' => $fullPath]);
            return null;
        }

        $mime = mime_content_type($fullPath);
        $data = file_get_contents($fullPath);

        return 'data:' . $mime . ';base64,' . base64_encode($data);
    }

    /**
     * Shared: full filtered result set (no pagination), scoped to the 'student'
     * role only via studentScope() — this is the single point that guarantees
     * PDF and Excel exports can never leak a non-student user, since both call
     * this same method rather than building their own query.
     */
    private function filteredExportRows(Request $request)
    {
        $fullNameExpr = "TRIM(REPLACE(CONCAT(first_name, ' ', COALESCE(NULLIF(middle_name, ''), ''), ' ', last_name), '  ', ' '))";

        $query = $this->studentScope(
            DB::table('users')
                ->selectRaw("id, userID, first_name, middle_name, last_name, email, phone_number, status, gender, county, sub_county, ward, created_at, {$fullNameExpr} as full_name")
                ->whereNull('deleted_at')
        );

        $query = $this->applyFilters($query, $request);

        return $query->orderBy('first_name')->get();
    }

    /**
     * Human-readable summary of active filters. No "Role" filter here — unlike
     * the general Users export, this listing is already scoped to one role
     * (student), so a role-filter line would be redundant noise.
     */
    private function activeFilterSummary(Request $request): string
    {
        $parts = [];

        if ($status = $request->input('filter_status')) {
            $parts[] = 'Status: ' . ucfirst($status);
        }
        if ($gender = $request->input('filter_gender')) {
            $parts[] = 'Gender: ' . ucfirst($gender);
        }
        if ($search = trim((string) $request->input('search_value'))) {
            $parts[] = 'Search: "' . $search . '"';
        }

        return $parts ? implode('  |  ', $parts) : 'No filters applied — showing all students';
    }

    /**
     * Shared WHERE conditions across data(), exportPdf(), and exportExcel() —
     * status/gender/search only. Deliberately no role filter (see note above).
     */
    private function applyFilters($query, Request $request)
    {
        if ($status = $request->input('filter_status')) {
            $query->where('status', $status);
        }

        if ($gender = $request->input('filter_gender')) {
            $query->where('gender', $gender);
        }

        $searchValue = trim((string) $request->input('search_value', $request->input('search.value')));

        if ($searchValue !== '') {
            $fullNameExpr = "TRIM(REPLACE(CONCAT(first_name, ' ', COALESCE(NULLIF(middle_name, ''), ''), ' ', last_name), '  ', ' '))";

            $query->where(function ($q) use ($searchValue, $fullNameExpr) {
                $q->whereRaw("{$fullNameExpr} LIKE ?", ["%{$searchValue}%"])
                    ->orWhere('email', 'like', "%{$searchValue}%")
                    ->orWhere('phone_number', 'like', "%{$searchValue}%")
                    ->orWhere('userID', 'like', "%{$searchValue}%");
            });
        }

        return $query;
    }

    public function toggleStatus(User $user)
    {
        $user->update(['status' => $user->status === 'active' ? 'pending' : 'active']);

        return back()->with('success', "{$user->full_name} was " . ($user->status === 'active' ? 'activated' : 'deactivated') . '.');
    }
}
