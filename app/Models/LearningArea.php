<?php

namespace App\Models;

use App\Models\Concerns\HasStringId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class LearningArea extends Model
{
    use HasStringId, softDeletes;

    protected $fillable = ['id', 'name', 'code', 'description', 'is_compulsory', 'status'];

    protected function casts(): array
    {
        return ['is_compulsory' => 'boolean'];
    }

    public function gradeLevels(): BelongsToMany
    {
        return $this->belongsToMany(GradeLevel::class, 'grade_level_learning_area')->withTimestamps();
    }

    public function pathways(): BelongsToMany
    {
        return $this->belongsToMany(Pathway::class, 'pathway_learning_area')->withTimestamps();
    }
}
