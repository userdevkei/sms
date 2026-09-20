<?php

namespace App\Models;

use App\Models\Concerns\HasStringId;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Votehead extends Model
{
    use HasStringId, softDeletes, LogsActivity;

    protected $fillable = ['id', 'name', 'code', 'category', 'description', 'status'];
}
