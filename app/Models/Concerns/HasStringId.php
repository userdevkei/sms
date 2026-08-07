<?php

namespace App\Models\Concerns;

use App\Services\Common;

trait HasStringId
{
    protected static function bootHasStringId(): void
    {
        static::creating(function ($model) {
            $key = $model->getKeyName();
            if (empty($model->{$key})) {
//                $model->{$key} = (new Common())->IDGenerator($model->getTable(), $key);
                $model->{$key} = (new Common())->IDgenerator();
            }
        });
    }

    public function initializeHasStringId(): void
    {
        $this->keyType = 'string';
        $this->incrementing = false;
    }
}
