<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ElectronicBillingConfig extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'environment',
        'sol_user',
        'sol_password',
        'certificate_path',
        'certificate_password',
        'office_ubigeo',
        'office_address',
        'office_urbanization',
        'office_district',
        'office_province',
        'office_department',
        'office_country_code',
        'invoice_series',
        'receipt_series',
        'is_active',
        'extra_settings',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'extra_settings' => 'array',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
