<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRoomRequest;
use App\Http\Requests\UpdateRoomRequest;
use App\Models\Hostel;
use App\Models\Room;
use Illuminate\Http\JsonResponse;

class RoomController extends Controller
{
    public function store(StoreRoomRequest $request, Hostel $hostel)
    {
        $validated = $request->validated();
        $validated['hostel_id'] = $hostel->id;

        $room = Room::query()->create($validated);

        return redirect()->route('accommodation.hostels.show', $hostel->id)->with('success', "Room \"{$room->name}\" was added successfully.");
    }

    public function update(UpdateRoomRequest $request, Room $room)
    {
        $room->update($request->validated());

        return redirect()->route('accommodation.hostels.show', $room->hostel_id)->with('success', "Room \"{$room->name}\" was updated successfully.");
    }

    public function destroy(Room $room): JsonResponse
    {
        if ($room->activeAllocations()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'This room has active allocations. Vacate all students before deleting.',
            ], 422);
        }

        $room->delete();

        return response()->json(['success' => true, 'message' => 'Room deleted successfully.']);
    }
}
