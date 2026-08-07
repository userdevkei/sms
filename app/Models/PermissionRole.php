<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PermissionRole extends Model
{
    protected $fillable = ['role_id', 'permission_id'];
    protected $casts = [
        'permission_id' => 'string',
        'role_id' => 'string',
    ];
}
