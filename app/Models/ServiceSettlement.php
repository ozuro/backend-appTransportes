<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceSettlement extends Model
{
    use HasFactory;

    /**
     * Liquidacion economica de un servicio terminado.
     */
    protected $fillable = [
        'company_id',
        'transport_service_id',
        'vehicle_id',
        'driver_id',
        'recorded_by_user_id',
        'gross_amount',
        'company_amount',
        'owner_amount',
        'driver_amount',
        'expense_amount',
        'status',
        'settled_at',
        'notes',
    ];

    protected $casts = [
        'gross_amount' => 'decimal:2',
        'company_amount' => 'decimal:2',
        'owner_amount' => 'decimal:2',
        'driver_amount' => 'decimal:2',
        'expense_amount' => 'decimal:2',
        'settled_at' => 'datetime',
    ];

    /**
     * Empresa duena de la operacion.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Servicio que origina esta liquidacion.
     */
    public function transportService()
    {
        return $this->belongsTo(TransportService::class);
    }

    /**
     * Unidad usada en el servicio.
     */
    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * Chofer principal del servicio.
     */
    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    /**
     * Usuario que registro la liquidacion.
     */
    public function recordedByUser()
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }
}
