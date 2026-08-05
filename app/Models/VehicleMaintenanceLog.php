<?php

namespace App\Models;

use App\Models\Concerns\HasStringId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehicleMaintenanceLog extends Model
{
    use HasStringId;

    protected $fillable = [
        'id', 'vehicle_id', 'service_date', 'description', 'cost',
        'odometer_reading', 'next_service_date', 'serviced_by',
    ];

    protected function casts(): array
    {
        return [
            'service_date'      => 'date',
            'next_service_date' => 'date',
            'cost'              => 'decimal:2',
        ];
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }
}
