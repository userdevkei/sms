<?php

namespace App\Models;

use App\Models\Concerns\HasStringId;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pathway extends Model
{
    use HasStringId, softDeletes, LogsActivity;

    protected $fillable = ['id', 'name', 'code', 'description', 'status'];

    public function learningAreas(): BelongsToMany
    {
        return $this->belongsToMany(LearningArea::class, 'pathway_learning_area')->withTimestamps();
    }

    public function streams(): HasMany
    {
        return $this->hasMany(Stream::class);
    }
}
