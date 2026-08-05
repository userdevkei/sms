<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DashboardController extends Controller
{
    public function dashboard()
    {
        return view('admin.dashboard');
    }

/*    public function settings()
    {
        $settings = Setting::pluck('value', 'key');
        return view('admin.settings', compact('settings'));
    }*/

    public function settings()
    {
        $settings = Setting::pluck('value', 'key'); // unchanged

        return view('admin.settings', [
            'settings'        => $settings,
            'smsGateways'     => \App\Models\Gateway::where('type', 'sms')->with('credentials')->latest('created_at')->get(),
            'paymentGateways' => \App\Models\Gateway::where('type', 'payment')->with('credentials')->latest('created_at')->get(),
            'emailGateways'   => \App\Models\Gateway::where('type', 'email')->with('credentials')->latest('created_at')->get(),
        ]);
    }

    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'school_name'      => 'required|string',
            'tagline'          => 'nullable|string',
            'motto'            => 'nullable|string',
            'primary_color'    => 'nullable|string',
            'secondary_color'  => 'nullable|string',
            'sidebar_color'    => 'nullable|string',
            'address'          => 'nullable|string',
            'phone'            => 'nullable|string',
            'currency'         => 'nullable|string',
            'email'            => 'nullable|email',
            'logo'             => 'nullable|file|image|max:2048',
            'favicon'          => 'nullable|file|mimes:ico,png,svg|max:512',
        ]);

        foreach ($validated as $key => $value) {
            if (in_array($key, ['logo', 'favicon'], true)) {
                continue; // handled separately below
            }
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        if ($request->hasFile('logo')) {
            $this->storeBrandingFile($request->file('logo'), 'logo_path');
        }

        if ($request->hasFile('favicon')) {
            $this->storeBrandingFile($request->file('favicon'), 'favicon_path');
        }

        return redirect()->back()->with('success', 'Settings updated successfully');
    }

    /**
     * Store an uploaded branding file (logo/favicon) at Files/branding,
     * remove the previous file for that setting key, and persist the new path.
     */
    protected function storeBrandingFile(\Illuminate\Http\UploadedFile $file, string $settingKey): void
    {
        $filename = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME))
            . '-' . time()
            . '.' . $file->getClientOriginalExtension();

        $destination = base_path('Files/branding');

        if (! is_dir($destination)) {
            mkdir($destination, 0755, true);
        }

        $oldPath = Setting::where('key', $settingKey)->value('value');
        if ($oldPath && file_exists(base_path($oldPath))) {
            unlink(base_path($oldPath));
        }

        $file->move($destination, $filename);

        Setting::updateOrCreate(
            ['key' => $settingKey],
            ['value' => 'Files/branding/' . $filename]
        );
    }
}
