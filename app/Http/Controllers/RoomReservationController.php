<?php

namespace App\Http\Controllers;

use App\Http\Requests\ApproveRoomReservationRequest;
use App\Http\Requests\StoreRoomReservationRequest;
use App\Models\Hostel;
use App\Models\Room;
use App\Models\RoomAllocation;
use App\Models\RoomReservation;
use App\Models\User;
use Illuminate\Http\Request;

class RoomReservationController extends Controller
{
    public function index()
    {
        $pending = RoomReservation::query()->where('status', 'pending')
            ->with(['student', 'hostel', 'preferredRoom'])->latest()->get();

        $resolved = RoomReservation::query()->whereIn('status', ['approved', 'rejected', 'allocated', 'cancelled'])
            ->with(['student', 'hostel', 'reviewedBy'])->latest('reviewed_at')->limit(50)->get();

        return view('accommodation.reservations.index', compact('pending', 'resolved'));
    }

    public function create()
    {
        $students = User::query()
            ->whereHas('roles', fn ($q) => $q->where('slug', 'student'))
            ->whereDoesntHave('currentRoomAllocation')
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'middle_name', 'last_name', 'userID', 'gender']);

        $hostels = Hostel::query()->where('status', 'active')->orderBy('name')->get();

        return view('accommodation.reservations.create', compact('students', 'hostels'));
    }

    public function store(StoreRoomReservationRequest $request)
    {
        $validated = $request->validated();

        $student = User::query()->findOrFail($validated['user_id']);
        $hostel = Hostel::query()->findOrFail($validated['hostel_id']);

        if ($hostel->gender !== 'mixed' && $hostel->gender !== $student->gender) {
            return back()->withInput()->with('error', "{$hostel->name} is a {$hostel->gender} hostel and cannot be reserved for a {$student->gender} student.");
        }

        $validated['requested_by'] = $request->user()->id;
        $validated['status'] = 'pending';

        RoomReservation::query()->create($validated);

        return redirect()->route('accommodation.reservations.index')->with('success', 'Reservation request submitted.');
    }

    /** AJAX: rooms with space, belonging to a given hostel, for the approval dropdown. */
    public function roomsForHostel(Hostel $hostel)
    {
        $rooms = $hostel->rooms()->where('status', 'active')->get()
            ->filter(fn ($room) => $room->hasSpace())
            ->map(fn ($room) => [
                'id'    => $room->id,
                'name'  => "{$room->name} ({$room->availableBeds()} bed(s) free)",
            ])
            ->values();

        return response()->json($rooms);
    }

    public function approve(ApproveRoomReservationRequest $request, RoomReservation $reservation)
    {
        abort_unless($reservation->status === 'pending', 422, 'This reservation has already been decided.');

        $room = Room::query()->with('hostel')->findOrFail($request->validated()['room_id']);
        abort_unless($room->hasSpace(), 422, 'That room has no available beds.');

        // Defensive re-check: hostel gender policy or the student's record may have
        // changed between the reservation being submitted and it being approved.
        if ($room->hostel->gender !== 'mixed' && $room->hostel->gender !== $reservation->student->gender) {
            abort(422, "This room is in a {$room->hostel->gender} hostel and cannot be assigned to {$reservation->student->full_name}.");
        }

        RoomAllocation::query()->create([
            'user_id'        => $reservation->user_id,
            'room_id'        => $room->id,
            'reservation_id' => $reservation->id,
            'academic_year'  => $reservation->academic_year,
            'term'           => $reservation->term,
            'status'         => 'active',
            'allocated_on'   => now(),
            'allocated_by'   => $request->user()->id,
        ]);

        $reservation->update([
            'status'      => 'allocated',
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        return back()->with('success', "{$reservation->student->full_name} was allocated to {$room->full_name}.");
    }

    public function reject(Request $request, RoomReservation $reservation)
    {
        abort_unless($request->user()?->hasPermission('accommodation.manage'), 403);
        abort_unless($reservation->status === 'pending', 422, 'This reservation has already been decided.');

        $validated = $request->validate(['notes' => ['nullable', 'string', 'max:500']]);

        $reservation->update([
            'status'      => 'rejected',
            'notes'       => $validated['notes'] ?? $reservation->notes,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        return back()->with('success', 'Reservation rejected.');
    }

    public function destroy(RoomReservation $reservation)
    {
        abort_unless(request()->user()?->hasPermission('accommodation.manage'), 403);
        abort_unless(in_array($reservation->status, ['pending', 'rejected']), 422, 'Only pending or rejected reservations can be cancelled.');

        $reservation->update(['status' => 'cancelled']);

        return back()->with('success', 'Reservation cancelled.');
    }
}
