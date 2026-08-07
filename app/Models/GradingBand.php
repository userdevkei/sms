<?php

namespace App\Models;

use App\Models\Concerns\HasStringId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GradingBand extends Model
{
    use HasStringId, softDeletes;

    protected $fillable = ['id', 'min_score', 'max_score', 'letter_grade', 'points', 'remark'];

    public static function letterFor(float $score): ?string
    {
        return static::query()
            ->where('min_score', '<=', $score)
            ->where('max_score', '>=', $score)
            ->value('letter_grade');
    }
}
