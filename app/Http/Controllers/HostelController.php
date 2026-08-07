<?php


namespace App\Http\Controllers;

use App\Http\Requests\StoreHostelRequest;
use App\Http\Requests\UpdateHostelRequest;
use App\Models\Hostel;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class HostelController extends Controller
{
    public function index()
    {
        $hostels = Hostel::query()->with('warden')->withCount('rooms')->orderBy('name')->get()
            ->map(function ($hostel) {
                $hostel->total_capacity = $hostel->totalCapacity();
                $hostel->total_occupied = $hostel->totalOccupied();
                return $hostel;
            });

        return view('accommodation.hostels.index', compact('hostels'));
    }

    public function create()
    {
        $wardenCandidates = User::query()
            ->whereHas('roles', fn($q) => $q->where('slug', 'hostel_warden'))
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'middle_name', 'last_name']);

        return view('accommodation.hostels.create', compact('wardenCandidates'));
    }

    public function store(StoreHostelRequest $request)
    {
        $hostel = Hostel::query()->create($request->validated());

        return redirect()->route('accommodation.hostels.index')->with('success', "\"{$hostel->name}\" was added successfully.");
    }

    public function show(Hostel $hostel)
    {
        $hostel->load(['warden', 'rooms' => fn($q) => $q->orderBy('name')]);

        $hostel->rooms->each(function ($room) {
            $room->occupied_beds = $room->occupiedBeds();
            $room->available_beds = $room->availableBeds();
        });

        return view('accommodation.hostels.show', compact('hostel'));
    }

    public function edit(Hostel $hostel)
    {
        $wardenCandidates = User::query()
            ->whereHas('roles', fn($q) => $q->where('slug', 'hostel_warden'))
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'middle_name', 'last_name']);

        return view('accommodation.hostels.edit', compact('hostel', 'wardenCandidates'));
    }

    public function update(UpdateHostelRequest $request, Hostel $hostel)
    {
        $hostel->update($request->validated());

        return redirect()->route('accommodation.hostels.index')->with('success', "\"{$hostel->name}\" was updated successfully.");
    }

    public function destroy(Hostel $hostel): JsonResponse
    {
        if ($hostel->rooms()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'This hostel has rooms defined under it. Remove those first.',
            ], 422);
        }

        $hostel->delete();

        return response()->json(['success' => true, 'message' => 'Hostel deleted successfully.']);
    }
}
