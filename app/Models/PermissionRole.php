<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class PermissionRole extends Model
{
    use LogsActivity;
    protected $fillable = ['role_id', 'permission_id'];
    protected $casts = [
        'permission_id' => 'string',
        'role_id' => 'string',
    ];
}
