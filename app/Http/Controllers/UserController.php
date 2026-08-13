<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Imports\UsersImport;
use App\Models\Role;
use App\Models\RoleUser;
use App\Models\User;
use App\Services\Common\FileUploadService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Mpdf\Mpdf;

class UserController extends Controller
{
    public function index()
    {
        $roles = Role::query()->where('slug', '!=', 'student')->orderBy('name')->get(['id', 'name']);
        return view('users.index', compact('roles'));
    }

    /**
     * Server-side DataTables endpoint.
     * Deliberately raw query builder (not Eloquent hydration) and a
     * restricted column list — this is the listing that has to stay fast
     * as the user count grows, so it avoids the overhead of full models,
     * relationship loading, and unnecessary casts on every row.
     */
    public function data(Request $request): JsonResponse
    {
        $draw = (int) $request->input('draw', 1);
        $start = (int) $request->input('start', 0);
        $length = max(1, (int) $request->input('length', 25));
        $fullNameExpr = "TRIM(REPLACE(CONCAT(first_name, ' ', COALESCE(NULLIF(middle_name, ''), ''), ' ', last_name), '  ', ' '))";

        $columnMap = [
            1 => 'full_name',
            2 => 'userID',
            3 => 'email',
            4 => 'phone_number',
            6 => 'status',
            7 => 'created_at',
        ];
        $orderColumnIndex = (int) $request->input('order.0.column', 7);
        $orderColumn = $columnMap[$orderColumnIndex] ?? 'created_at';
        $orderDir = $request->input('order.0.dir') === 'asc' ? 'asc' : 'desc';

        /*$baseQuery = DB::table('users')
            ->selectRaw("id, userID, first_name, middle_name, last_name, email, phone_number, status, avatar, gender, created_at, {$fullNameExpr} as full_name")
            ->whereNull('deleted_at');*/
        $baseQuery = DB::table('users')
            ->selectRaw("id, userID, first_name, middle_name, last_name, email, phone_number, status, avatar, gender, created_at, {$fullNameExpr} as full_name")
            ->whereNull('deleted_at')
            ->whereNotExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('role_users')
                    ->join('roles', 'roles.id', '=', 'role_users.role_id')
                    ->whereColumn('role_users.user_id', 'users.id')
                    ->where('roles.slug', 'student');
            });

        $totalRecords = (clone $baseQuery)->count();

        $filteredQuery = $this->applyFilters(clone $baseQuery, $request);
        $filteredRecords = (clone $filteredQuery)->count();

        $users = $filteredQuery->orderBy($orderColumn, $orderDir)
            ->offset($start)
            ->limit($length)
            ->get();

        // Batch-fetch role names for just this page — avoids N+1 queries.
        $userIds = $users->pluck('id')->all();
        $rolesByUser = DB::table('role_users')
            ->join('roles', 'roles.id', '=', 'role_users.role_id')
            ->whereIn('role_users.user_id', $userIds)
            ->select('role_users.user_id', 'roles.name')
            ->get()
            ->groupBy('user_id');

        $data = $users->values()->map(function ($user, $index) use ($rolesByUser) {
            $roleNames = ($rolesByUser[$user->id] ?? collect())->pluck('name')->implode(', ') ?: '—';
            $avatarUrl = $user->avatar
                ? route('file', ['path' => $user->avatar])
                : route('file', ['path' => 'Files/images/avatar.png']);

            return [
                'sn'         => $index + 1, // continuous row number across pages, not just 1..25 per page
                'id'         => $user->id,
                'avatar'     => $avatarUrl,
                'name'       => $user->full_name,
                'gender'     => ucfirst($user->gender),
                'userID'     => $user->userID ?: '—',
                'email'      => $user->email,
                'phone'      => $user->phone_number ?: '—',
                'roles'      => $roleNames,
                'status'     => $user->status,
                'created_at' => Carbon::parse($user->created_at)->format('d M Y'),
                'profile_url'   => route('users.profile', $user->id),
                'edit_url'   => route('users.edit', $user->id),
                'delete_url' => route('users.destroy', $user->id),
            ];
        });

        return response()->json([
            'draw'            => $draw,
            'recordsTotal'    => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data'            => $data,
        ]);
    }

    public function create()
    {
        $roles = Role::query()->orderBy('name')->get(['id', 'name']);
        $counties = config('counties');
        return view('users.create', compact('roles', 'counties'));
    }

    public function store(StoreUserRequest $request)
    {
        $validated = $request->validated();

        if ($request->hasFile('avatar')) {
            $validated['avatar'] = FileUploadService::store($request->file('avatar'), 'Files/images');
        }

        $validated['password'] = Hash::make($validated['password']);
        $roleIds = $validated['roles'] ?? [];
        unset($validated['roles']);

        $user = User::query()->create($validated);
        $user->roles()->sync($roleIds);

        return redirect()->route('users.index')->with('success', "{$user->full_name} was added successfully.");
    }

    public function edit(User $user)
    {
        $roles = Role::query()->orderBy('name')->get(['id', 'name']);
        $userRoleIds = $user->roles()->pluck('roles.id')->all();
        $counties = config('counties');
        return view('users.edit', compact('user', 'roles', 'userRoleIds', 'counties'));
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $validated = $request->validated();

        if ($request->hasFile('avatar')) {
            FileUploadService::delete($user->avatar);
            $validated['avatar'] = FileUploadService::store($request->file('avatar'), 'Files/images');
        }

        if (! empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $roleIds = $validated['roles'] ?? [];
        unset($validated['roles']);

        $user->update($validated);
        $user->roles()->sync($roleIds);

        return redirect()->route('users.index')->with('success', "{$user->full_name} was updated successfully.");
    }

    public function destroy(User $user)
    {
        $user->delete(); // soft delete — deleted_at column already exists
        return response()->json(['success' => true, 'message' => 'User deleted successfully.']);
    }

    public function toggleStatus(User $user)
    {
        $user->update(['status' => $user->status === 'active' ? 'pending' : 'active']);

        return back()->with('success', "{$user->full_name} was " . ($user->status === 'active' ? 'activated' : 'deactivated') . '.');
    }

    public function profile($id)
    {
        $user = User::query()->findOrFail($id);
        return view('users.profile', compact('user'));
    }

    public function exportPdf(Request $request)
    {
        $users = $this->filteredExportRows($request);
        $logoPath = route('file', ['path' => setting('logo_path')]);
        $faviconPath = route('file', ['path' => setting('favicon_path')]);

        $html = view('users.export-pdf', [
            'users'       => $users,
            'filters'     => $this->activeFilterSummary($request),
            'generatedAt' => now()->format('d M Y, H:i'),
            'schoolName'  => setting('school_name', config('app.name')),
            'logoPath'    => $this->resolveImageBase64(setting('logo_path')),
            'faviconPath' => $this->resolveImageBase64(setting('favicon_path')),
        ])->render();

        $mpdf = new Mpdf([
            'format'        => 'A4-L',   // landscape
            'margin_left'   => 10,
            'margin_right'  => 5,
            'margin_top'    => 5,
            'margin_bottom' => 10,
            'margin_header' => 8,        // space reserved for header (if using SetHTMLHeader)
            'margin_footer' => 8,        // space reserved for footer (if using SetHTMLFooter)
        ]);
        $motto = setting('motto');
        $mpdf->SetHTMLFooter('
            <table style="width: 100%; font-size: 9px; color: #9ca3af;">
                <tr>
                    <td style="text-align: left;">' . setting('motto', setting('school_name')) . '</td>
                    <td style="text-align: right;">Page {PAGENO} of {nbpg}</td>
                </tr>
            </table>
        ');

        $mpdf->WriteHTML($html);

        return response($mpdf->Output('system-users-' . now()->format('Y-m-d-His') . '.pdf', 'S'))
            ->header('Content-Type', 'application/pdf');
    }

    private function resolveImageBase64(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        $fullPath = base_path($path); // since path already starts with "Files/..."

        if (! file_exists($fullPath)) {
            \Log::info('Logo file not found', ['path' => $fullPath]);
            return null;
        }

        $mime = mime_content_type($fullPath);
        $data = file_get_contents($fullPath);

        return 'data:' . $mime . ';base64,' . base64_encode($data);
    }

    /**
     * Shared: build the full filtered result set (no pagination) plus batch-loaded
     * role names, used by both export formats so PDF and Excel always contain
     * identical rows.
     *
     * Excludes anyone with the 'student' role — this export is for staff/other
     * system users only, mirroring the same exclusion already applied to
     * UserController::data() so the roster, the DataTable, and both exports
     * all agree on who counts as a "user" here.
     */
    private function filteredExportRows(Request $request)
    {
        $fullNameExpr = "TRIM(REPLACE(CONCAT(first_name, ' ', COALESCE(NULLIF(middle_name, ''), ''), ' ', last_name), '  ', ' '))";

        $query = DB::table('users')
            ->selectRaw("id, userID, first_name, middle_name, last_name, email, phone_number, status, gender, county, sub_county, ward, created_at, {$fullNameExpr} as full_name")
            ->whereNull('deleted_at')
            ->whereNotExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('role_users')
                    ->join('roles', 'roles.id', '=', 'role_users.role_id')
                    ->whereColumn('role_users.user_id', 'users.id')
                    ->where('roles.slug', 'student');
            });

        $query = $this->applyFilters($query, $request);

        $users = $query->orderBy('first_name')->get();

        $userIds = $users->pluck('id')->all();
        $rolesByUser = DB::table('role_users')
            ->join('roles', 'roles.id', '=', 'role_users.role_id')
            ->whereIn('role_users.user_id', $userIds)
            ->select('role_users.user_id', 'roles.name')
            ->get()
            ->groupBy('user_id');

        return $users->map(function ($user) use ($rolesByUser) {
            $user->role_names = ($rolesByUser[$user->id] ?? collect())->pluck('name')->implode(', ') ?: '—';
            return $user;
        });
    }

    /**
     * Human-readable summary of active filters, shown in the export header so
     * anyone looking at the file later knows it's a subset, not the full list.
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
        if ($roleId = $request->input('filter_role')) {
            $roleName = Role::query()->where('id', $roleId)->value('name');
            if ($roleName) {
                $parts[] = 'Role: ' . $roleName;
            }
        }
        if ($search = trim((string) $request->input('search_value'))) {
            $parts[] = 'Search: "' . $search . '"';
        }

        return $parts ? implode('  |  ', $parts) : 'No filters applied — showing all users';
    }


    public function exportExcel(Request $request)
    {
        $users = $this->filteredExportRows($request);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('System Users');

        // Title + filter context rows above the actual table
        $sheet->setCellValue('A1', setting('school_name', config('app.name')));
        $sheet->mergeCells('A1:K1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        $sheet->setCellValue('A2', 'Generated: ' . now()->format('d M Y, H:i'));
        $sheet->mergeCells('A2:K2');
        $sheet->getStyle('A2')->getFont()->setSize(9)->getColor()->setRGB('999999');

        $headerRow = 3;
        $headers = ['#', 'User ID', 'Full Name', 'Gender', 'Email', 'Phone', 'User Type', 'County', 'Sub County', 'Ward', 'Status'];
        $sheet->fromArray($headers, null, "A{$headerRow}");
        $sheet->getStyle("A{$headerRow}:K{$headerRow}")->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $sheet->getStyle("A{$headerRow}:K{$headerRow}")->getFill()
            ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('0B3D62');
        $sheet->getStyle("A{$headerRow}:K{$headerRow}")->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

        $row = $headerRow + 1;
        foreach ($users as $index => $user) {
            $location = collect([$user->ward, $user->sub_county, $user->county])->filter()->implode(', ') ?: '—';

            $sheet->fromArray([
                $index + 1,
                $user->userID ?: '—',
                $user->full_name,
                $user->gender ? ucfirst($user->gender) : '—',
                $user->email,
                $user->phone_number ?: '—',
                $user->role_names,
                $user->county,
                $user->sub_county,
                $user->ward,
                ucfirst($user->status),
            ], null, "A{$row}");

            $row++;
        }

        foreach (range('A', 'K') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $filename = 'system-users-' . now()->format('Y-m-d-His') . '.xlsx';

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Shared WHERE conditions across the DataTables endpoint and both export
     * methods, so "export filtered data" always means the same thing as
     * "what's currently shown in the table" — no risk of the two drifting apart.
     */
    private function applyFilters($query, Request $request)
    {
        if ($status = $request->input('filter_status')) {
            $query->where('status', $status);
        }

        if ($gender = $request->input('filter_gender')) {
            $query->where('gender', $gender);
        }

        if ($roleId = $request->input('filter_role')) {
            $query->whereExists(function ($sub) use ($roleId) {
                $sub->select(DB::raw(1))
                    ->from('role_users')
                    ->whereColumn('role_users.user_id', 'users.id')
                    ->where('role_users.role_id', $roleId);
            });
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

    public function import()
    {
        return view('users.import');
    }

    public function preview(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,xlsx,xls|max:5120',
        ]);

        $rows = (new UsersImport)->toCollection($request->file('file'));

        $seenEmails = [];
        $seenUserIds = [];
        $preview = [];

        foreach ($rows as $index => $row) {
            $data = [
                'first_name'   => trim((string) ($row['first_name'] ?? '')),
                'last_name'    => trim((string) ($row['last_name'] ?? '')),
                'middle_name'  => trim((string) ($row['middle_name'] ?? '')),
                'user_id'      => trim((string) ($row['user_id'] ?? '')),
                'email'        => strtolower(trim((string) ($row['email'] ?? ''))),
                'gender'       => strtolower(trim((string) ($row['gender'] ?? ''))),
                'phone' => trim((string) ($row['phone'] ?? '')),
                'county' => trim((string) ($row['county'] ?? '')),
                'sub_county' => trim((string) ($row['sub_county'] ?? '')),
                'ward' => trim((string) ($row['ward'] ?? '')),
            ];

            $errors = [];

            if ($data['first_name'] === '') $errors[] = 'First name is required';
            if ($data['last_name'] === '') $errors[] = 'Last name is required';

            if ($data['user_id'] === '') {
                $errors[] = 'User ID is required';
            } elseif (isset($seenUserIds[$data['user_id']])) {
                $errors[] = 'Duplicate User ID within this file (row ' . $seenUserIds[$data['user_id']] . ')';
            } elseif (User::where('userID', $data['user_id'])->exists()) {
                $errors[] = 'User ID already exists in the system';
            }

            if ($data['email'] === '' || ! filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'A valid email is required';
            } elseif (isset($seenEmails[$data['email']])) {
                $errors[] = 'Duplicate email within this file (row ' . $seenEmails[$data['email']] . ')';
            } elseif (User::where('email', $data['email'])->exists()) {
                $errors[] = 'Email already exists in the system';
            }

            if (! in_array($data['gender'], ['male', 'female'], true)) {
                $errors[] = 'Gender must be male or female';
            }

            // Only mark as "seen" if the value itself was valid and non-empty,
            // so a blank user_id/email doesn't falsely flag later blanks as duplicates
            if ($data['user_id'] !== '') $seenUserIds[$data['user_id']] = $index + 2; // +2 = account for header row + 0-index
            if ($data['email'] !== '') $seenEmails[$data['email']] = $index + 2;

            $preview[] = [
                'row'    => $index + 2,
                'data'   => $data,
                'errors' => $errors,
                'valid'  => empty($errors),
            ];
        }

        $token = (string) Str::uuid();
        Cache::put('user-import:' . $token, $preview, now()->addMinutes(20));

        return view('users.import-preview', [
            'token'   => $token,
            'rows'    => $preview,
            'validCount'   => collect($preview)->where('valid', true)->count(),
            'invalidCount' => collect($preview)->where('valid', false)->count(),
        ]);
    }

    public function importUsers(Request $request)
    {
        $request->validate([
            'token'    => 'required|string',
            'selected' => 'nullable|array',
            'selected.*' => 'integer',
        ]);

        $preview = Cache::get('user-import:' . $request->input('token'));

        if (! $preview) {
            return redirect()->route('users.import.create')
                ->with('error', 'This import session has expired. Please upload the file again.');
        }

        $selected = array_flip($request->input('selected', []));
        $imported = 0;
        $skipped = 0;

        foreach ($preview as $i => $entry) {
            if (! isset($selected[$i]) || ! $entry['valid']) {
                $skipped++;
                continue;
            }

            // Re-check uniqueness at commit time in case another import/row landed
            // between preview and commit (protects against race conditions)
            $exists = User::where('userID', $entry['data']['user_id'])
                ->orWhere('email', $entry['data']['email'])
                ->exists();

            if ($exists) {
                $skipped++;
                continue;
            }

            $success = User::create([
                            'first_name'   => $entry['data']['first_name'],
                            'last_name'    => $entry['data']['last_name'],
                            'middle_name'  => $entry['data']['middle_name'] ?: null,
                            'userID'       => $entry['data']['user_id'],
                            'email'        => $entry['data']['email'],
                            'gender'       => $entry['data']['gender'],
                            'phone_number' => $entry['data']['phone'] ?: null,
                            'password'     => Hash::make($entry['data']['user_id']),
                            'county'       => $entry['data']['county'],
                            'sub_county'   => $entry['data']['sub_county'],
                            'ward'          => $entry['data']['ward'],
                            'status'       => 'active',
                        ]);
            if ($success) {
                $role = Role::where('slug', 'student')->first();
                RoleUser::firstOrCreate(['user_id' => $success->id, 'role_id' => $role->id]);
            }

            $imported++;
        }

        Cache::forget('user-import:' . $request->input('token'));

        return redirect()->route('users.index')
            ->with('success', "Import complete: {$imported} user(s) created, {$skipped} skipped.");
    }

    public function template()
    {
        return response()->download(base_path('Files/template/StudentTemplate.xlsx'));
    }
}
