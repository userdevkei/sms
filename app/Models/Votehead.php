<?php

namespace App\Models;

use App\Models\Concerns\HasStringId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Votehead extends Model
{
    use HasStringId, softDeletes;

    protected $fillable = ['id', 'name', 'code', 'category', 'description', 'status'];
}
