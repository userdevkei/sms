<?php

namespace App\Services;

use App\Models\Log;
use Illuminate\Database\Eloquent\Model;

class ActivityLogService
{

    public static function log(string $action, ?Model $model = null, ?array $oldValues = null, ?array $newValues = null): void
    {
        if (!app()->runningInConsole()) {
            Log::create([
                'user_id' => auth()->id(),
                'action' => $action,
                'model_type' => $model ? get_class($model) : null,
                'model_id' => $model?->id,
                'old_values' => $oldValues,
                'new_values' => $newValues,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        }
    }
}
