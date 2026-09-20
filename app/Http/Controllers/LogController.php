<?php

namespace App\Http\Controllers;

use App\Models\Log;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class LogController extends Controller
{
    public function index()
    {
        $users = User::orderBy('first_name')->get(['id', 'first_name', 'last_name']);
        $actions = Log::query()->distinct()->orderBy('action')->pluck('action');
        $modelTypes = Log::query()->whereNotNull('model_type')->distinct()->pluck('model_type')
            ->mapWithKeys(fn ($type) => [$type => class_basename($type)])
            ->sort();

        return view('audit.logs.index', compact('users', 'actions', 'modelTypes'));
    }

    private function applyFilters(Request $request)
    {
        $query = Log::query()->with('user');

        if ($userId = $request->input('filter_user')) $query->where('user_id', $userId);
        if ($action = $request->input('filter_action')) $query->where('action', $action);
        if ($modelType = $request->input('filter_model_type')) $query->where('model_type', $modelType);
        if ($dateFrom = $request->input('filter_date_from')) $query->whereDate('created_at', '>=', $dateFrom);
        if ($dateTo = $request->input('filter_date_to')) $query->whereDate('created_at', '<=', $dateTo);

        if ($search = trim((string) $request->input('search.value'))) {
            $query->where(function ($q) use ($search) {
                $q->where('action', 'like', "%{$search}%")
                    ->orWhere('model_type', 'like', "%{$search}%")
                    ->orWhere('model_id', 'like', "%{$search}%")
                    ->orWhere('ip_address', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($u) => $u->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%"));
            });
        }

        return $query;
    }

    public function data(Request $request): JsonResponse
    {
        $draw = (int) $request->input('draw', 1);
        $start = (int) $request->input('start', 0);
        $length = max(1, (int) $request->input('length', 25));

        $query = $this->applyFilters($request);

        // Count before search narrows it, using a clone so filters (not search) define "total".
        $totalRecords = (clone $query)->count();
        $filteredRecords = $query->count(); // search already applied inside applyFilters()

        $logs = $query->orderByDesc('created_at')->offset($start)->limit($length)->get();

        $data = $logs->map(fn ($log) => [
            'id'          => $log->id,
            'date'        => $log->created_at->format('d M Y, H:i:s'),
            'user'        => $log->user ? trim($log->user->first_name.' '.$log->user->last_name) : 'System',
            'action'      => $log->action,
            'model'       => $log->model_label ?: '—',
            'model_id'    => $log->model_id ?: '—',
            'ip_address'  => $log->ip_address ?: '—',
            'details_url' => route('logs.details', $log->id),
        ]);

        return response()->json(['draw' => $draw, 'recordsTotal' => $totalRecords, 'recordsFiltered' => $filteredRecords, 'data' => $data]);
    }

    public function details(Log $log): JsonResponse
    {
        abort_unless(request()->user()?->hasPermission('logs.view'), 403);

        return response()->json([
            'date'        => $log->created_at->format('d M Y, H:i:s'),
            'user'        => $log->user ? trim($log->user->first_name.' '.$log->user->last_name) : 'System',
            'action'      => $log->action,
            'model'       => $log->model_label,
            'model_id'    => $log->model_id,
            'ip_address'  => $log->ip_address,
            'user_agent'  => $log->user_agent,
            'old_values'  => $log->old_values,
            'new_values'  => $log->new_values,
        ]);
    }

    public function export(Request $request)
    {
        abort_unless($request->user()?->hasPermission('logs.view'), 403);

        $logs = $this->applyFilters($request)->orderBy('created_at')->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Audit Log');

        $headers = ['#', 'Date/Time', 'User', 'Action', 'Model', 'Model ID', 'IP Address', 'Old Values', 'New Values'];
        $sheet->fromArray($headers, null, 'A1');
        $sheet->getStyle('A1:I1')->getFont()->setBold(true);
        $sheet->getStyle('A1:I1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('122744');
        $sheet->getStyle('A1:I1')->getFont()->getColor()->setRGB('FFFFFF');

        $row = 2;
        foreach ($logs as $i => $log) {
            $sheet->fromArray([
                $i + 1,
                $log->created_at->format('d M Y H:i:s'),
                $log->user ? trim($log->user->first_name.' '.$log->user->last_name) : 'System',
                $log->action,
                $log->model_label ?: '—',
                $log->model_id ?: '—',
                $log->ip_address ?: '—',
                $log->old_values ? json_encode($log->old_values) : '—',
                $log->new_values ? json_encode($log->new_values) : '—',
            ], null, "A{$row}");
            $row++;
        }

        foreach (range('A', 'G') as $col) $sheet->getColumnDimension($col)->setAutoSize(true);
        $sheet->getColumnDimension('H')->setWidth(40);
        $sheet->getColumnDimension('I')->setWidth(40);

        $filename = 'audit-log-'.now()->format('Y-m-d_His').'.xlsx';
        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(fn () => $writer->save('php://output'), $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
