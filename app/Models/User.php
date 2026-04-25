<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'first_name',
        'last_name',
        'email',
        'phone',
        'document_type',
        'document_number',
        'firebase_uid',
        'avatar_url',
        'password',
        'auth_provider',
        'is_active',
        'last_login_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function companies()
    {
        return $this->belongsToMany(Company::class)
            ->withPivot(['role_id', 'is_owner', 'is_active', 'joined_at'])
            ->withTimestamps();
    }

    /**
     * Unidades donde el usuario es el responsable o propietario economico.
     */
    public function ownedVehicles()
    {
        return $this->hasMany(Vehicle::class, 'owner_user_id');
    }

    /**
     * Asignaciones creadas por el usuario administrador o coordinador.
     */
    public function assignedDriverVehicleAssignments()
    {
        return $this->hasMany(DriverVehicleAssignment::class, 'assigned_by_user_id');
    }

    /**
     * Liquidaciones registradas por este usuario.
     */
    public function recordedServiceSettlements()
    {
        return $this->hasMany(ServiceSettlement::class, 'recorded_by_user_id');
    }

    /**
     * Ingresos de caja registrados por este usuario.
     */
    public function recordedCashIncomes()
    {
        return $this->hasMany(CashIncome::class, 'recorded_by_user_id');
    }
}
