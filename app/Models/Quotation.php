<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Quotation extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'client_id',
        'quotation_code',
        'service_type',
        'status',
        'origin_address',
        'destination_address',
        'estimated_distance_km',
        'quoted_amount',
        'cargo_description',
        'notes',
    ];

    protected $casts = [
        'estimated_distance_km' => 'decimal:2',
        'quoted_amount' => 'decimal:2',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
