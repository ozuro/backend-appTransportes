<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'client_type',
        'document_type',
        'document_number',
        'name',
        'business_name',
        'email',
        'phone',
        'secondary_phone',
        'address',
        'district',
        'province',
        'department',
        'category',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function getRucAttribute(): ?string
    {
        $number = preg_replace('/\D+/', '', (string) $this->document_number);
        $type = strtoupper((string) $this->document_type);

        return $number !== '' && (in_array($type, ['RUC', '6'], true) || strlen($number) === 11)
            ? $number
            : null;
    }

    public function getDniAttribute(): ?string
    {
        $number = preg_replace('/\D+/', '', (string) $this->document_number);
        $type = strtoupper((string) $this->document_type);

        return $number !== '' && (in_array($type, ['DNI', '1'], true) || strlen($number) === 8)
            ? $number
            : null;
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function transportServices()
    {
        return $this->hasMany(TransportService::class);
    }
}
