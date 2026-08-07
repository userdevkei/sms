<?php

namespace App\Models;

use App\Models\Concerns\HasStringId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vehicle extends Model
{
    use HasStringId, SoftDeletes;

    protected $fillable = [
        'id', 'registration_number', 'make', 'model', 'year_of_manufacture',
        'capacity', 'color', 'logbook_number', 'insurance_expiry',
        'inspection_expiry', 'status', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'insurance_expiry'  => 'date',
            'inspection_expiry' => 'date',
        ];
    }

    public function maintenanceLogs(): HasMany
    {
        return $this->hasMany(VehicleMaintenanceLog::class)->latest('service_date');
    }

    public function routeAssignments(): HasMany
    {
        return $this->hasMany(RouteAssignment::class);
    }

    public function currentAssignment(): ?RouteAssignment
    {
        return $this->routeAssignments()->where('status', 'active')->first();
    }

    public function getInsuranceExpiringSoonAttribute(): bool
    {
        return $this->insurance_expiry && $this->insurance_expiry->isBetween(now(), now()->addDays(30));
    }

    public function getInspectionExpiringSoonAttribute(): bool
    {
        return $this->inspection_expiry && $this->inspection_expiry->isBetween(now(), now()->addDays(30));
    }
}
