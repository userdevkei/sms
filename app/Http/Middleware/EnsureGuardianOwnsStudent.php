<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;

class EnsureGuardianOwnsStudent
{
    public function handle(Request $request, Closure $next)
    {
        $guardian = $request->user();

        $param = $request->route('child');
        $id    = $param instanceof User ? $param->getKey() : $param;

        $student = User::find($id);
        abort_if(! $student, 404);

        // Ownership check: the guardian must have an active link to this student.
        abort_unless($guardian && $guardian->guardsStudent($student->id), 403, 'This student is not linked to your account.');

        // Controllers receive the verified model, never a raw ID.
        $request->route()->setParameter('child', $student);
        $request->attributes->set(
            'guardian_link',
            $guardian->students()->where('users.id', $student->id)->first()->pivot
        );

        return $next($request);
    }
}
