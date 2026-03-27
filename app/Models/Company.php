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
}
