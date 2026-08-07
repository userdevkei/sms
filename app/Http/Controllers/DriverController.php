<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDriverRequest;
use App\Http\Requests\UpdateDriverRequest;
use App\Models\Driver;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DriverController extends Controller
{
    public function index()
    {
        return view('transport.drivers.index');
    }

    public function data(Request $request): JsonResponse
    {
        $draw = (int) $request->input('draw', 1);
        $start = (int) $request->input('start', 0);
        $length = max(1, (int) $request->input('length', 25));
        $searchValue = trim((string) $request->input('search.value'));

        $fullNameExpr = "TRIM(REPLACE(CONCAT(first_name, ' ', COALESCE(NULLIF(middle_name, ''), ''), ' ', last_name), '  ', ' '))";

        $query = User::query()
            ->whereHas('roles', fn ($q) => $q->where('slug', 'driver'))
            ->with('driver')
            ->whereNull('deleted_at');

        // filter_status here means driver-record status, not user account status —
        // "no_license" is a synthetic value for users who haven't been completed yet.
        if ($status = $request->input('filter_status')) {
            if ($status === 'no_license') {
                $query->whereDoesntHave('driver');
            } else {
                $query->whereHas('driver', fn ($q) => $q->where('status', $status));
            }
        }

        $totalRecords = (clone $query)->count();

        if ($searchValue !== '') {
            $query->where(function ($q) use ($searchValue, $fullNameExpr) {
                $q->whereRaw("{$fullNameExpr} LIKE ?", ["%{$searchValue}%"])
                    ->orWhere('phone_number', 'like', "%{$searchValue}%")
                    ->orWhereHas('driver', fn ($dq) => $dq->where('license_number', 'like', "%{$searchValue}%"));
            });
        }

        $filteredRecords = (clone $query)->count();

        $users = $query->orderByRaw($fullNameExpr)->offset($start)->limit($length)->get();

        $data = $users->values()->map(function ($user, $index) use ($start) {
            $driver = $user->driver; // null if role assigned but license details not yet completed

            return [
                'sn'              => $start + $index + 1,
                'avatar'          => $user->avatar_url,
                'name'            => $user->full_name,
                'license_number'  => $driver?->license_number ?? '—',
                'license_expiry'  => $driver?->license_expiry?->format('d M Y') ?? '—',
                'license_soon'    => $driver?->license_expiring_soon ?? false,
                'phone'           => $user->phone_number ?: '—',
                'status'          => $driver?->status ?? 'no_license',
                'has_driver_record' => (bool) $driver,
                'edit_url'        => $driver
                    ? route('transport.drivers.edit', $driver->id)
                    : route('transport.drivers.create', ['user_id' => $user->id]),
                'delete_url'      => $driver ? route('transport.drivers.destroy', $driver->id) : null,
            ];
        });

        return response()->json([
            'draw' => $draw, 'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords, 'data' => $data,
        ]);
    }

    public function create(Request $request)
    {
        $eligibleUsers = User::query()
            ->whereHas('roles', fn ($q) => $q->where('slug', 'driver'))
            ->whereDoesntHave('driver')
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'middle_name', 'last_name', 'phone_number']);

        $preselectedUserId = $request->query('user_id');

        return view('transport.drivers.create', compact('eligibleUsers', 'preselectedUserId'));
    }

    public function store(StoreDriverRequest $request)
    {
        $driver = Driver::query()->create($request->validated());

        return redirect()->route('transport.drivers.index')->with('success', "{$driver->full_name} was added as a driver successfully.");
    }

    public function edit(Driver $driver)
    {
        $driver->load('user');

        return view('transport.drivers.edit', compact('driver'));
    }

    public function update(UpdateDriverRequest $request, Driver $driver)
    {
        $driver->update($request->validated());

        return redirect()->route('transport.drivers.index')->with('success', "{$driver->full_name}'s driver record was updated successfully.");
    }

    public function destroy(Driver $driver): JsonResponse
    {
        if ($driver->routeAssignments()->where('status', 'active')->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'This driver is actively assigned to a route. End that assignment before deleting.',
            ], 422);
        }

        $driver->delete(); // removes the driver record only — the underlying User account is untouched

        return response()->json(['success' => true, 'message' => 'Driver record deleted successfully.']);
    }
}
