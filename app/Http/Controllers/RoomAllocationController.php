<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRoomAllocationRequest;
use App\Models\Hostel;
use App\Models\RoomAllocation;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoomAllocationController extends Controller
{
    public function index(Request $request)
    {
        $query = RoomAllocation::query()->with(['student', 'room.hostel']);

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        } else {
            $query->where('status', 'active'); // default view: who's currently housed
        }

        if ($hostelId = $request->query('hostel')) {
            $query->whereHas('room', fn ($q) => $q->where('hostel_id', $hostelId));
        }

        $allocations = $query->latest('allocated_on')->get();
        $hostels = Hostel::query()->orderBy('name')->get(['id', 'name']);

        return view('accommodation.allocations.index', compact('allocations', 'hostels'));
    }

    public function create()
    {
        $students = User::query()
            ->whereHas('roles', fn ($q) => $q->where('slug', 'student'))
            ->whereDoesntHave('currentRoomAllocation')
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'middle_name', 'last_name', 'userID', 'gender']);

        $hostels = Hostel::query()->where('status', 'active')->with('rooms')->orderBy('name')->get();

        return view('accommodation.allocations.create', compact('students', 'hostels'));
    }

    public function store(StoreRoomAllocationRequest $request)
    {
        $validated = $request->validated();

        $room = \App\Models\Room::query()->with('hostel')->findOrFail($validated['room_id']);
        $student = \App\Models\User::query()->findOrFail($validated['user_id']);

        abort_unless($room->hasSpace(), 422, 'That room has no available beds.');
        abort_if($student->currentRoomAllocation, 422, 'This student already has an active room allocation.');

        if ($room->hostel->gender !== 'mixed' && $room->hostel->gender !== $student->gender) {
            abort(422, "This room is in a {$room->hostel->gender} hostel and cannot be assigned to a {$student->gender} student.");
        }

        $validated['allocated_by'] = $request->user()->id;
        $validated['status'] = 'active';
        $validated['allocated_on'] = $validated['allocated_on'] ?? now();

        RoomAllocation::query()->create($validated);

        return redirect()->route('accommodation.allocations.index')->with('success', 'Student allocated successfully.');
    }

    public function vacate(Request $request, RoomAllocation $allocation)
    {
        abort_unless($request->user()?->hasPermission('accommodation.manage'), 403);
        abort_unless($allocation->status === 'active', 422, 'This allocation is not active.');

        $allocation->update(['status' => 'ended', 'vacated_on' => now()]);

        return back()->with('success', "{$allocation->student->full_name} was vacated from {$allocation->room->full_name}.");
    }

    public function destroy(RoomAllocation $allocation): JsonResponse
    {
        $allocation->delete();

        return response()->json(['success' => true, 'message' => 'Allocation record removed.']);
    }
}
