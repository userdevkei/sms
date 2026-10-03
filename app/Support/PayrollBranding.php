<?php


namespace App\Support;

class PayrollBranding
{
    public static function get(): array
    {
        $s = self::settings();
        $pick = fn(array $keys, $default = null) => collect($keys)->map(fn($k) => $s[$k] ?? null)->first(fn($v) => filled($v)) ?? $default;

        return [
            'name' => $pick(['school_name', 'name'], config('app.name')),
            'tagline' => $pick(['school_motto', 'school_tagline', 'motto', 'tagline']),
            'address' => $pick(['school_address', 'address', 'postal_address']),
            'phone' => $pick(['school_phone', 'phone', 'contact_phone']),
            'email' => $pick(['school_email', 'email', 'contact_email']),
            'pin' => $pick(['school_kra_pin', 'kra_pin', 'employer_kra_pin', 'employer_pin']),
            'logo' => self::logo($pick(['school_logo', 'logo', 'logo_path'])),
            'color' => '#0b4a7e',
        ];
    }

    private static function settings(): array
    {
        try {
            $m = \App\Models\Setting::class;
            if (method_exists($m, 'allSettings')) {
                return collect($m::allSettings())->all();
            }

            return $m::query()->pluck('value', 'key')->all();
        } catch (\Throwable $e) {
            return [];
        }
    }

    private static function logo(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        $fullPath = base_path($path); // since path already starts with "Files/..."

        if (! file_exists($fullPath)) {
            \Log::info('Logo file not found', ['path' => $fullPath]);
            return null;
        }

        $mime = mime_content_type($fullPath);
        $data = file_get_contents($fullPath);

        return 'data:' . $mime . ';base64,' . base64_encode($data);
    }

    /** Embed the logo as a data URI so mPDF never needs a URL or path. */
}
