<?php

namespace App\Models;

use App\Models\Concerns\HasStringId;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class IncomeCategory extends Model
{
    use HasStringId, softDeletes, LogsActivity;

    protected $fillable = ['name', 'slug', 'description', 'status'];

    public function transactions()
    {
        return $this->hasMany(IncomeTransactionItem::class);
    }

    protected static function booted()
    {
        static::creating(fn ($c) => $c->slug ??= Str::slug($c->name));
    }
}
