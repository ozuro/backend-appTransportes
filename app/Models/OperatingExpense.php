<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OperatingExpense extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'vehicle_id',
        'transport_service_id',
        'category',
        'payment_method',
        'amount',
        'expense_date',
        'supplier_name',
        'description',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'expense_date' => 'date',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function transportService()
    {
        return $this->belongsTo(TransportService::class);
    }
}
