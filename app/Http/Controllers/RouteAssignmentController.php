<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRouteAssignmentRequest;
use App\Models\Driver;
use App\Models\RouteAssignment;
use App\Models\TransportRoute;
use App\Models\Vehicle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RouteAssignmentController extends Controller
{
    public function index()
    {
        $routes = TransportRoute::query()->where('status', 'active')->orderBy('name')->get(['id', 'name']);

        $vehicles = Vehicle::query()->where('status', 'active')->orderBy('registration_number')
            ->get(['id', 'registration_number', 'capacity']);

        // Corrected: Driver no longer has first_name/last_name directly —
        // those live on the related User now, so we eager-load and sort in PHP
        // (a DB-level orderBy on a related table's column needs a join, and for
        // a dropdown-sized list this is simpler and avoids an explicit join).
        $drivers = Driver::query()
            ->where('status', 'active')
            ->with('user:id,first_name,middle_name,last_name')
            ->get()
            ->sortBy(fn ($driver) => $driver->full_name)
            ->values();

        return view('transport.assignments.index', compact('routes', 'vehicles', 'drivers'));
    }

    public function data(Request $request): JsonResponse
    {
        $draw = (int) $request->input('draw', 1);
        $start = (int) $request->input('start', 0);
        $length = max(1, (int) $request->input('length', 25));

        $query = RouteAssignment::query()->with(['route', 'vehicle', 'driver']);

        if ($status = $request->input('filter_status')) {
            $query->where('status', $status);
        }

        $totalRecords = (clone $query)->count();
        $filteredRecords = $totalRecords; // no free-text search on this listing

        $assignments = $query->latest('start_date')->offset($start)->limit($length)->get();

        $data = $assignments->values()->map(function ($a, $index) use ($start) {
            return [
                'sn'         => $start + $index + 1,
                'route'      => $a->route?->name ?? '—',
                'vehicle'    => $a->vehicle?->registration_number ?? '—',
                'driver'     => $a->driver?->full_name ?? '—',
                'term'       => $a->term ?: '—',
                'start_date' => $a->start_date?->format('d M Y') ?? '—',
                'end_date'   => $a->end_date?->format('d M Y') ?? '—',
                'status'     => $a->status,
                'end_url'    => route('transport.route-assignments.end', $a->id),
                'delete_url' => route('transport.route-assignments.destroy', $a->id),
            ];
        });

        return response()->json([
            'draw' => $draw, 'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords, 'data' => $data,
        ]);
    }

    public function store(StoreRouteAssignmentRequest $request)
    {
        $validated = $request->validated();

        // A vehicle or driver shouldn't be actively double-booked across routes.
        $conflict = RouteAssignment::query()->where('status', 'active')
            ->where(fn ($q) => $q->where('vehicle_id', $validated['vehicle_id'])->orWhere('driver_id', $validated['driver_id']))
            ->exists();

        if ($conflict) {
            return back()->withInput()->with('error', 'That vehicle or driver already has an active assignment on another route.');
        }

        RouteAssignment::query()->create($validated);

        return redirect()->route('transport.route-assignments.index')->with('success', 'Assignment created successfully.');
    }

    public function end(RouteAssignment $routeAssignment)
    {
        if (! request()->user()?->hasPermission('transport.manage')) {
            abort(403);
        }

        $routeAssignment->update(['status' => 'ended', 'end_date' => now()]);

        return back()->with('success', 'Assignment ended.');
    }

    public function destroy(RouteAssignment $routeAssignment): JsonResponse
    {
        $routeAssignment->delete();

        return response()->json(['success' => true, 'message' => 'Assignment removed.']);
    }
}
