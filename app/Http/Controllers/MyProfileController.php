<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProfileRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MyProfileController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();

        return view('profile.show', ['user' => $user]);
    }

    public function edit(Request $request)
    {
        $user = $request->user();
        abort_if($user->hasRole('student'), 403); // students don't edit — see show() for their read-only view

        return view('profile.edit', compact('user'));
    }

    public function update(UpdateProfileRequest $request)
    {
        $user = $request->user();
        $validated = $request->validated();

        if ($request->hasFile('avatar')) {
            $path = 'Files/images/avatars/' . Str::random(20) . '.' . $request->file('avatar')->extension();
            $request->file('avatar')->move(base_path(dirname($path)), basename($path));
            $validated['avatar'] = $path;
        }

        $user->update($validated);

        return redirect()->route('profile.show')->with('success', 'Profile updated successfully.');
    }
}
