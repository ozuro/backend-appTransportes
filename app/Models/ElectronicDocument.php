<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ElectronicDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'client_id',
        'transport_service_id',
        'quotation_id',
        'document_type',
        'series',
        'correlative',
        'status',
        'issue_date',
        'currency_code',
        'subtotal_amount',
        'tax_amount',
        'total_amount',
        'payload',
        'xml_path',
        'cdr_path',
        'pdf_path',
        'sunat_ticket',
        'sunat_response_code',
        'sunat_response_message',
        'sent_at',
        'accepted_at',
        'voided_at',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'subtotal_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'payload' => 'array',
        'sent_at' => 'datetime',
        'accepted_at' => 'datetime',
        'voided_at' => 'datetime',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function transportService()
    {
        return $this->belongsTo(TransportService::class);
    }

    public function quotation()
    {
        return $this->belongsTo(Quotation::class);
    }
}
