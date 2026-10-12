<?php

namespace App\Http\Controllers\Guardian;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Results\StudentResultsOverview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class GuardianResultsController extends Controller
{
    public function index(Request $request, User $child, StudentResultsOverview $overview)
    {
        $url = function (string $kind, array $p) use ($child): ?string {
            $name = "guardian.students.results.{$kind}-pdf";

            if (! Route::has($name)) {
                return null;
            }

            // The service passes params in the student portal's order; map them to the guardian routes.
            return match ($kind) {
                'term'       => route($name, ['child' => $child->id, 'reportId' => $p[0]]),
                'assessment' => route($name, ['child' => $child->id, 'enrollmentId' => $p[3], 'termId' => $p[1], 'name' => $p[2]]),
                'year'       => route($name, ['child' => $child->id, 'enrollmentId' => $p[2]]),
            };
        };

        return view('guardian.results', array_merge($overview->build($child, $url), ['child' => $child]));
    }
}
