<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransportService extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'client_id',
        'vehicle_id',
        'driver_id',
        'service_code',
        'service_type',
        'status',
        'origin_address',
        'origin_reference',
        'destination_address',
        'destination_reference',
        'scheduled_start_at',
        'scheduled_end_at',
        'actual_start_at',
        'actual_end_at',
        'quoted_amount',
        'final_amount',
        'payment_status',
        'cargo_description',
        'observations',
        'incidents',
    ];

    protected $casts = [
        'scheduled_start_at' => 'datetime',
        'scheduled_end_at' => 'datetime',
        'actual_start_at' => 'datetime',
        'actual_end_at' => 'datetime',
        'quoted_amount' => 'decimal:2',
        'final_amount' => 'decimal:2',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }
}
