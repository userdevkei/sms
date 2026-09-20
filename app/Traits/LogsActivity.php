<?php

namespace App\Traits;

use App\Services\ActivityLogService;

trait LogsActivity
{
    public static function bootLogsActivity()
    {
        if (app()->runningInConsole()) {
            return false;
        }

        static::created(function ($model) {
            ActivityLogService::log('created', $model, null, $model->toArray());
        });

        static::updated(function ($model) {
            ActivityLogService::log('updated', $model, $model->getOriginal(), $model->getDirty());
        });

        static::deleted(function ($model) {
            ActivityLogService::log('deleted', $model, $model->toArray(), null);
        });
    }
}
