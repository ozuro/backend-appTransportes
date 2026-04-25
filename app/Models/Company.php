<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'trade_name',
        'legal_name',
        'ruc',
        'email',
        'phone',
        'country_code',
        'currency_code',
        'timezone',
        'address',
        'district',
        'province',
        'department',
        'is_active',
        'settings',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'settings' => 'array',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class)
            ->withPivot(['role_id', 'is_owner', 'is_active', 'joined_at'])
            ->withTimestamps();
    }

    public function clients()
    {
        return $this->hasMany(Client::class);
    }

    public function vehicles()
    {
        return $this->hasMany(Vehicle::class);
    }

    public function drivers()
    {
        return $this->hasMany(Driver::class);
    }

    public function transportServices()
    {
        return $this->hasMany(TransportService::class);
    }

    public function quotations()
    {
        return $this->hasMany(Quotation::class);
    }

    public function operatingExpenses()
    {
        return $this->hasMany(OperatingExpense::class);
    }

    public function cashIncomes()
    {
        return $this->hasMany(CashIncome::class);
    }

    /**
     * Historial de asignaciones entre choferes y unidades de la empresa.
     * Esto soporta tiempo completo, medio tiempo o relevo.
     */
    public function driverVehicleAssignments()
    {
        return $this->hasMany(DriverVehicleAssignment::class);
    }

    /**
     * Liquidaciones por servicio para separar montos de empresa,
     * propietario y chofer sin mezclar la caja general.
     */
    public function serviceSettlements()
    {
        return $this->hasMany(ServiceSettlement::class);
    }
}
