<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Driver extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'user_id',
        'assigned_vehicle_id',
        'first_name',
        'last_name',
        'document_type',
        'document_number',
        'phone',
        'email',
        'license_number',
        'license_category',
        'license_expires_at',
        'status',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'license_expires_at' => 'date',
        'is_active' => 'boolean',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function assignedVehicle()
    {
        return $this->belongsTo(Vehicle::class, 'assigned_vehicle_id');
    }

    public function transportServices()
    {
        return $this->hasMany(TransportService::class);
    }
}
