<?php

namespace App\Models;

use App\Models\Concerns\HasStringId;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Permission extends Model
{
    use HasStringId, softDeletes, LogsActivity;
    protected $fillable = ['id', 'name', 'module', 'description', 'is_principle'];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'permission_role', 'permission_id', 'role_id')
            ->withTimestamps();
    }
}
