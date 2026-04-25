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

    public function getProviderAttribute(): string
    {
        return (string) data_get($this->extra_settings, 'provider', 'greenter');
    }

    public function getApiBaseUrlAttribute(): ?string
    {
        return data_get($this->extra_settings, 'api_base_url');
    }

    public function getApiTokenAttribute(): ?string
    {
        return data_get($this->extra_settings, 'api_token');
    }

    public function getSireClientIdAttribute(): ?string
    {
        return data_get($this->extra_settings, 'sire_client_id');
    }

    public function getSireClientSecretAttribute(): ?string
    {
        return data_get($this->extra_settings, 'sire_client_secret');
    }

    public function getSireUsernameAttribute(): ?string
    {
        return data_get($this->extra_settings, 'sire_username');
    }

    public function getSireSalesEndpointAttribute(): ?string
    {
        return data_get($this->extra_settings, 'sire_sales_endpoint');
    }

    public function getSirePurchasesEndpointAttribute(): ?string
    {
        return data_get($this->extra_settings, 'sire_purchases_endpoint');
    }

    public function getInitialReceiptCorrelativeAttribute(): ?int
    {
        $value = data_get($this->extra_settings, 'initial_receipt_correlative');

        return is_numeric($value) ? (int) $value : null;
    }

    public function getInitialInvoiceCorrelativeAttribute(): ?int
    {
        $value = data_get($this->extra_settings, 'initial_invoice_correlative');

        return is_numeric($value) ? (int) $value : null;
    }
}
