<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CashIncome extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'transport_service_id',
        'vehicle_id',
        'driver_id',
        'recorded_by_user_id',
        'amount',
        'concept',
        'beneficiary_type',
        'beneficiary_id',
        'note',
        'received_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'received_at' => 'datetime',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function transportService()
    {
        return $this->belongsTo(TransportService::class);
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    public function recordedByUser()
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }

    /**
     * Beneficiario flexible del ingreso.
     * Permite asociar la cobranza a empresa, propietario o chofer.
     */
    public function beneficiary()
    {
        return $this->morphTo();
    }
}
