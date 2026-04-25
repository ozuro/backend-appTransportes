<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DriverVehicleAssignment extends Model
{
    use HasFactory;

    /**
     * Historial de que chofer usa que unidad y bajo que modalidad.
     */
    protected $fillable = [
        'company_id',
        'driver_id',
        'vehicle_id',
        'assigned_by_user_id',
        'assignment_type',
        'is_primary',
        'is_active',
        'starts_at',
        'ends_at',
        'notes',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'is_active' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    /**
     * Empresa a la que pertenece la asignacion.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Chofer asignado a la unidad.
     */
    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    /**
     * Unidad asignada.
     */
    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * Usuario que registro o aprobo la asignacion.
     */
    public function assignedByUser()
    {
        return $this->belongsTo(User::class, 'assigned_by_user_id');
    }
}
