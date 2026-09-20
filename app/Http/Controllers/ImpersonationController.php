<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ImpersonationController extends Controller
{
    private const SESSION_KEY = 'impersonator_id';

    public function start(Request $request, User $user)
    {
        $admin = $request->user();

        abort_unless($admin->hasPermission('users.impersonate'), 403);
        abort_if($request->session()->has(self::SESSION_KEY), 403, 'Stop the current impersonation first.');
        abort_if($admin->is($user), 422, 'You cannot impersonate yourself.');

        // Privilege-escalation guard: never let an impersonator become another impersonator.
        abort_if($user->hasPermission('users.impersonate'), 403, 'You cannot impersonate this user.');

        // Swap identity, rotate the session ID (prevents fixation), THEN store the original id,
        // because regenerate() keeps data but Auth::login() must happen first for the new user.
        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->put(self::SESSION_KEY, $admin->getKey());

        Log::info('Impersonation started', [
            'impersonator_id' => $admin->getKey(),
            'target_id'       => $user->getKey(),
            'ip'              => $request->ip(),
        ]);

        return redirect('/')->with('success', "You are now impersonating {$user->name}.");
    }

    public function stop(Request $request)
    {
        $originalId = $request->session()->pull(self::SESSION_KEY);

        abort_unless($originalId, 403);

        $original = User::findOrFail($originalId);
        $targetId = $request->user()?->getKey();

        Auth::login($original);
        $request->session()->regenerate();

        Log::info('Impersonation stopped', [
            'impersonator_id' => $original->getKey(),
            'target_id'       => $targetId,
        ]);

        return redirect('/')->with('success', 'Returned to your own account.');
    }
}
