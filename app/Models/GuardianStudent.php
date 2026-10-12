<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class GuardianStudent extends Pivot
{
    protected $table = 'guardian_student';
    public $incrementing = true;

    protected $guarded = [];

    protected $casts = [
        'is_primary' => 'boolean',
        'can_pay'    => 'boolean',
    ];
}
