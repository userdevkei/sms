<?php

namespace App\Models;

use App\Models\Concerns\HasStringId;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AssessmentType extends Model
{
    use HasStringId, softDeletes, LogsActivity;

    protected $fillable = ['id', 'name', 'scoring_mode', 'default_max_score', 'description', 'status'];

    public function isCompetencyBased(): bool
    {
        return $this->scoring_mode === 'competency';
    }
}
