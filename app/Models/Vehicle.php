<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'unit_type',
        'plate',
        'brand',
        'model',
        'year',
        'capacity_value',
        'capacity_unit',
        'mileage',
        'soat_expires_at',
        'technical_review_expires_at',
        'operational_status',
        'estimated_cost_per_km',
        'estimated_cost_per_service',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'year' => 'integer',
        'mileage' => 'integer',
        'capacity_value' => 'decimal:2',
        'estimated_cost_per_km' => 'decimal:2',
        'estimated_cost_per_service' => 'decimal:2',
        'soat_expires_at' => 'date',
        'technical_review_expires_at' => 'date',
        'is_active' => 'boolean',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function drivers()
    {
        return $this->hasMany(Driver::class, 'assigned_vehicle_id');
    }

    public function transportServices()
    {
        return $this->hasMany(TransportService::class);
    }
}
