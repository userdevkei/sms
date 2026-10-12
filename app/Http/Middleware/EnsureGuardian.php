<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureGuardian
{
    public function handle(Request $request, Closure $next)
    {
        abort_unless($request->user()?->isGuardian(), 403, 'Guardian access only.');

        return $next($request);
    }
}
