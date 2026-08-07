<?php

use App\Models\Setting;

if (! function_exists('setting')) {
    function setting(?string $key = null, mixed $default = null): mixed
    {
        if (is_null($key)) {
            return \App\Models\Setting::allSettings();
        }

        return \App\Models\Setting::get($key, $default);
    }
}
