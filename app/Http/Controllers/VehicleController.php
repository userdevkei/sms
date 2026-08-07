<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreVehicleRequest;
use App\Http\Requests\UpdateVehicleRequest;
use App\Models\Vehicle;
use App\Models\VehicleMaintenanceLog;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VehicleController extends Controller
{
    public function index()
    {
        return view('transport.vehicles.index');
    }

    public function data(Request $request): JsonResponse
    {
        $draw = (int) $request->input('draw', 1);
        $start = (int) $request->input('start', 0);
        $length = max(1, (int) $request->input('length', 25));
        $searchValue = trim((string) $request->input('search.value'));

        $columnMap = [1 => 'registration_number', 2 => 'make', 5 => 'capacity', 6 => 'status'];
        $orderColumn = $columnMap[(int) $request->input('order.0.column', 1)] ?? 'registration_number';
        $orderDir = $request->input('order.0.dir') === 'desc' ? 'desc' : 'asc';

        $query = Vehicle::query();

        if ($status = $request->input('filter_status')) {
            $query->where('status', $status);
        }

        $totalRecords = (clone $query)->count();

        if ($searchValue !== '') {
            $query->where(function ($q) use ($searchValue) {
                $q->where('registration_number', 'like', "%{$searchValue}%")
                    ->orWhere('make', 'like', "%{$searchValue}%")
                    ->orWhere('model', 'like', "%{$searchValue}%");
            });
        }

        $filteredRecords = (clone $query)->count();

        $vehicles = $query->orderBy($orderColumn, $orderDir)->offset($start)->limit($length)->get();

        $data = $vehicles->values()->map(function ($vehicle, $index) use ($start) {
            return [
                'sn'                 => $start + $index + 1,
                'registration_number'=> $vehicle->registration_number,
                'make_model'         => trim("{$vehicle->make} {$vehicle->model}") ?: '—',
                'capacity'           => $vehicle->capacity,
                'insurance_expiry'   => $vehicle->insurance_expiry?->format('d M Y') ?? '—',
                'insurance_soon'     => $vehicle->insurance_expiring_soon,
                'inspection_expiry'  => $vehicle->inspection_expiry?->format('d M Y') ?? '—',
                'inspection_soon'    => $vehicle->inspection_expiring_soon,
                'status'             => $vehicle->status,
                'show_url'           => route('transport.vehicles.show', $vehicle->id),
                'edit_url'           => route('transport.vehicles.edit', $vehicle->id),
                'delete_url'         => route('transport.vehicles.destroy', $vehicle->id),
            ];
        });

        return response()->json([
            'draw' => $draw, 'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords, 'data' => $data,
        ]);
    }

    public function create()
    {
        return view('transport.vehicles.create');
    }

    public function store(StoreVehicleRequest $request)
    {
        $vehicle = Vehicle::query()->create($request->validated());

        return redirect()->route('transport.vehicles.index')->with('success', "Vehicle {$vehicle->registration_number} was added successfully.");
    }

    public function show(Vehicle $vehicle)
    {
        return view('transport.vehicles.show', compact('vehicle'));
    }

    public function edit(Vehicle $vehicle)
    {
        return view('transport.vehicles.edit', compact('vehicle'));
    }

    public function update(UpdateVehicleRequest $request, Vehicle $vehicle)
    {
        $vehicle->update($request->validated());

        return redirect()->route('transport.vehicles.index')->with('success', "Vehicle {$vehicle->registration_number} was updated successfully.");
    }

    public function destroy(Vehicle $vehicle): JsonResponse
    {
        $activeAssignment = $vehicle->routeAssignments()->where('status', 'active')->exists();

        if ($activeAssignment) {
            return response()->json([
                'success' => false,
                'message' => 'This vehicle is actively assigned to a route. End that assignment before deleting.',
            ], 422);
        }

        $vehicle->delete();

        return response()->json(['success' => true, 'message' => 'Vehicle deleted successfully.']);
    }

    public function storeMaintenanceLog(Request $request, Vehicle $vehicle)
    {
        if (! $request->user()?->hasPermission('transport.manage')) {
            abort(403);
        }

        $validated = $request->validate([
            'service_date'      => ['required', 'date'],
            'description'       => ['required', 'string', 'max:255'],
            'cost'               => ['nullable', 'numeric', 'min:0'],
            'odometer_reading'   => ['nullable', 'integer', 'min:0'],
            'next_service_date'  => ['nullable', 'date', 'after_or_equal:service_date'],
            'serviced_by'        => ['nullable', 'string', 'max:150'],
        ]);

        $validated['vehicle_id'] = $vehicle->id;
        VehicleMaintenanceLog::query()->create($validated);

        return back()->with('success', 'Maintenance log added.');
    }
}
