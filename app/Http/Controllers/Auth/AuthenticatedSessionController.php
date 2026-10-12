<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

//    public function store(LoginRequest $request)/*: RedirectResponse*/
//    {
//        $request->authenticate();
//        $request->session()->regenerate();
//
//        return redirect()->intended(route('dashboard'));
//    }


    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();


        if ($request->user()->isGuardian()) {
            $request->session()->forget('url.intended'); // a stale admin URL would just 403 them

            return redirect()->route('guardian.dashboard');
        }

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
