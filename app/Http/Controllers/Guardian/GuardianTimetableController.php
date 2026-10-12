<?php

namespace App\Http\Controllers\Guardian;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Timetable\StudentTimetable;
use Illuminate\Http\Request;

class GuardianTimetableController extends Controller
{
    public function index(Request $request, User $child, StudentTimetable $timetable)
    {
        return view('guardian.timetable', [
            'child'    => $child,
            'sections' => $timetable->sectionsFor($child), // only ever the verified child
        ]);
    }
}
