<?php

namespace App\Models;

use App\Models\Concerns\HasStringId;
use Illuminate\Database\Eloquent\Model;

class Log extends Model
{
    use HasStringId;
    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = ['id', 'user_id', 'action', 'model_type', 'model_id',  'old_values', 'new_values', 'ip_address', 'mac_address', 'user_agent'];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function getModelLabelAttribute(): ?string
    {
        return $this->model_type ? class_basename($this->model_type) : null;
    }
}
