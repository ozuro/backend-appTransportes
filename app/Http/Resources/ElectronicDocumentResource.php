<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ElectronicDocumentResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'client_id' => $this->client_id,
            'transport_service_id' => $this->transport_service_id,
            'quotation_id' => $this->quotation_id,
            'document_type' => $this->document_type,
            'series' => $this->series,
            'correlative' => $this->correlative,
            'full_number' => $this->correlative
                ? sprintf('%s-%08d', $this->series, $this->correlative)
                : null,
            'status' => $this->status,
            'issue_date' => optional($this->issue_date)->format('Y-m-d'),
            'currency_code' => $this->currency_code,
            'subtotal_amount' => $this->subtotal_amount,
            'tax_amount' => $this->tax_amount,
            'total_amount' => $this->total_amount,
            'sunat_ticket' => $this->sunat_ticket,
            'sunat_response_code' => $this->sunat_response_code,
            'sunat_response_message' => $this->sunat_response_message,
            'xml_path' => $this->xml_path,
            'cdr_path' => $this->cdr_path,
            'pdf_path' => $this->pdf_path,
            'payload' => $this->payload ?? [],
            'client' => $this->whenLoaded('client', function () {
                return [
                    'id' => $this->client?->id,
                    'name' => $this->client?->name,
                    'document_type' => $this->client?->document_type,
                    'document_number' => $this->client?->document_number,
                ];
            }),
            'transport_service' => $this->whenLoaded('transportService', function () {
                return [
                    'id' => $this->transportService?->id,
                    'service_code' => $this->transportService?->service_code,
                ];
            }),
            'quotation' => $this->whenLoaded('quotation', function () {
                return [
                    'id' => $this->quotation?->id,
                    'quotation_code' => $this->quotation?->quotation_code,
                ];
            }),
            'sent_at' => optional($this->sent_at)->toIso8601String(),
            'accepted_at' => optional($this->accepted_at)->toIso8601String(),
            'voided_at' => optional($this->voided_at)->toIso8601String(),
            'created_at' => optional($this->created_at)->toIso8601String(),
            'updated_at' => optional($this->updated_at)->toIso8601String(),
        ];
    }
}
