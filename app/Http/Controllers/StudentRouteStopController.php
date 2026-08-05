<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStudentRouteStopRequest;
use App\Models\RouteStop;
use App\Models\StudentRouteStop;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class StudentRouteStopController extends Controller
{
    public function index()
    {
        $assignments = StudentRouteStop::query()
            ->with(['student', 'routeStop.route'])
            ->latest()
            ->get();

        return view('transport.student-route-stops.index', compact('assignments'));
    }

    public function create()
    {
        $students = User::query()
            ->whereHas('roles', fn ($q) => $q->where('slug', 'student'))
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'middle_name', 'last_name', 'userID']);

        $routeStops = RouteStop::query()->with('route')->orderBy('name')->get();

        return view('transport.student-route-stops.create', compact('students', 'routeStops'));
    }

    /**
     * Creates one StudentRouteStop row per selected student, all pointed at
     * the same route stop/year/term. The double-booking check already ran
     * per-student in the form request, so every id reaching here is clear
     * to assign.
     */
    public function store(StoreStudentRouteStopRequest $request)
    {
        $validated = $request->validated();

        foreach ($validated['user_ids'] as $userId) {
            StudentRouteStop::query()->create([
                'user_id'       => $userId,
                'route_stop_id' => $validated['route_stop_id'],
                'academic_year' => $validated['academic_year'],
                'term'          => $validated['term'],
                'status'        => 'active',
            ]);
        }

        $count = count($validated['user_ids']);

        return redirect()->route('finance.transport.student-route-stops.index')
            ->with('success', $count . ' student(s) assigned to route stop.');
    }

    public function destroy(StudentRouteStop $studentRouteStop): JsonResponse
    {
        abort_unless(request()->user()?->hasPermission('transport.manage'), 403);

        $studentRouteStop->delete(); // soft delete — invoices already generated still reference this row

        return response()->json(['success' => true, 'message' => 'Assignment removed.']);
    }
}
