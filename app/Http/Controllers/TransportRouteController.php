<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRouteRequest;
use App\Http\Requests\UpdateRouteRequest;
use App\Models\RouteStop;
use App\Models\TransportRoute;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransportRouteController extends Controller
{
    public function index()
    {
        return view('transport.routes.index');
    }

    public function data(Request $request): JsonResponse
    {
        $draw = (int) $request->input('draw', 1);
        $start = (int) $request->input('start', 0);
        $length = max(1, (int) $request->input('length', 25));
        $searchValue = trim((string) $request->input('search.value'));

        $query = TransportRoute::query()->withCount('stops');

        if ($status = $request->input('filter_status')) {
            $query->where('status', $status);
        }

        $totalRecords = (clone $query)->count();

        if ($searchValue !== '') {
            $query->where(function ($q) use ($searchValue) {
                $q->where('name', 'like', "%{$searchValue}%")
                    ->orWhere('code', 'like', "%{$searchValue}%");
            });
        }

        $filteredRecords = (clone $query)->count();

        $routes = $query->orderBy('name')->offset($start)->limit($length)->get();

        $data = $routes->values()->map(function ($route, $index) use ($start) {
            $assignment = $route->currentAssignment();
            $fareRange = $route->stops()->selectRaw('MIN(fare) as min_fare, MAX(fare) as max_fare')->first();

            return [
                'sn'          => $start + $index + 1,
                'name'        => $route->name,
                'code'        => $route->code ?: '—',
                'stops_count' => $route->stops_count,
                'fare_range'  => $fareRange && $fareRange->min_fare !== null
                    ? ($fareRange->min_fare == $fareRange->max_fare
                        ? 'KES ' . number_format($fareRange->min_fare, 0)
                        : 'KES ' . number_format($fareRange->min_fare, 0) . ' – ' . number_format($fareRange->max_fare, 0))
                    : '—',
                'vehicle'     => $assignment?->vehicle?->registration_number ?: 'Unassigned',
                'driver'      => $assignment?->driver?->full_name ?: 'Unassigned',
                'status'      => $route->status,
                'show_url'    => route('transport.transport-routes.show', $route->id),
                'edit_url'    => route('transport.transport-routes.edit', $route->id),
                'delete_url'  => route('transport.transport-routes.destroy', $route->id),
            ];
        });

        return response()->json([
            'draw' => $draw, 'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords, 'data' => $data,
        ]);
    }

    public function create()
    {
        return view('transport.routes.create');
    }

    public function store(StoreRouteRequest $request)
    {
        $validated = $request->validated();

        $route = DB::transaction(function () use ($validated) {
            $route = TransportRoute::query()->create([
                'name' => $validated['name'], 'code' => $validated['code'] ?? null,
                'description' => $validated['description'] ?? null, 'status' => $validated['status'],
            ]);

            foreach ($validated['stops'] as $index => $stop) {
                $route->stops()->create([
                    'name' => $stop['name'],
                    'sequence' => $index + 1,
                    'landmark_description' => $stop['landmark_description'] ?? null,
                    'fare' => $stop['fare'],
                ]);
            }

            return $route;
        });

        return redirect()->route('transport.transport-routes.index')->with('success', "Route \"{$route->name}\" was created successfully.");
    }

    public function show(TransportRoute $transportRoute)
    {
        $transportRoute->load('stops', 'assignments.vehicle', 'assignments.driver');

        return view('transport.routes.show', ['route' => $transportRoute]);
    }

    public function edit(TransportRoute $transportRoute)
    {
        $transportRoute->load('stops');

        return view('transport.routes.edit', ['route' => $transportRoute]);
    }

    /*public function update(UpdateRouteRequest $request, TransportRoute $transportRoute)
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated, $transportRoute) {
            $transportRoute->update([
                'name' => $validated['name'], 'code' => $validated['code'] ?? null,
                'description' => $validated['description'] ?? null, 'status' => $validated['status'],
            ]);

            $submittedStopIds = collect($validated['stops'])->pluck('id')->filter()->all();

            // Remove stops that were deleted client-side
            $transportRoute->stops()->whereNotIn('id', $submittedStopIds)->delete();

            foreach ($validated['stops'] as $index => $stopData) {
                $payload = [
                    'name' => $stopData['name'],
                    'sequence' => $index + 1,
                    'landmark_description' => $stopData['landmark_description'] ?? null,
                    'fare' => $stopData['fare'],
                ];

                if (! empty($stopData['id'])) {
                    RouteStop::query()->where('id', $stopData['id'])->where('route_id', $transportRoute->id)->update($payload);
                } else {
                    $transportRoute->stops()->create($payload);
                }
            }
        });

        return redirect()->route('transport.transport-routes.index')->with('success', "Route \"{$transportRoute->name}\" was updated successfully.");
    }*/

    public function update(UpdateRouteRequest $request, TransportRoute $transportRoute)
    {
        $validated = $request->validated();

        \Log::info('UPDATE ROUTE payload', [
            'route_id' => $transportRoute->id,
            'stops' => $validated['stops'],
        ]);

        DB::transaction(function () use ($validated, $transportRoute) {
            $transportRoute->update([
                'name' => $validated['name'], 'code' => $validated['code'] ?? null,
                'description' => $validated['description'] ?? null, 'status' => $validated['status'],
            ]);

            $submittedStopIds = collect($validated['stops'])->pluck('id')->filter()->all();

            $deleted = $transportRoute->stops()->whereNotIn('id', $submittedStopIds)->delete();
            \Log::info('Deleted stops not in submission', ['count' => $deleted, 'kept_ids' => $submittedStopIds]);

            foreach ($validated['stops'] as $index => $stopData) {
                $payload = [
                    'name' => $stopData['name'],
                    'sequence' => $index + 1,
                    'landmark_description' => $stopData['landmark_description'] ?? null,
                    'fare' => $stopData['fare'],
                ];

                if (! empty($stopData['id'])) {
                    $affected = $transportRoute->stops()->where('id', $stopData['id'])->update($payload);
                    \Log::info('Updated existing stop', ['id' => $stopData['id'], 'affected_rows' => $affected, 'payload' => $payload]);
                } else {
                    $new = $transportRoute->stops()->create($payload);
                    \Log::info('Created new stop', ['new_id' => $new->id]);
                }
            }
        });

        return redirect()->route('transport.transport-routes.index')->with('success', "Route \"{$transportRoute->name}\" was updated successfully.");
    }

    public function destroy(TransportRoute $transportRoute): JsonResponse
    {
        if ($transportRoute->assignments()->where('status', 'active')->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'This route has an active vehicle/driver assignment. End it before deleting the route.',
            ], 422);
        }

        $transportRoute->delete();

        return response()->json(['success' => true, 'message' => 'Route deleted successfully.']);
    }
}
