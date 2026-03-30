<?php

namespace App\Services\Sunat;

use App\Models\Client as LocalClient;
use App\Models\Company as LocalCompany;
use App\Models\ElectronicBillingConfig;
use App\Models\ElectronicDocument;
use Greenter\Model\Client\Client;
use Greenter\Model\Company\Address;
use Greenter\Model\Company\Company;
use Greenter\Model\Sale\FormaPagos\FormaPagoContado;
use Greenter\Model\Sale\Invoice;
use Greenter\Model\Sale\Legend;
use Greenter\Model\Sale\SaleDetail;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class GreenterDocumentService
{
    public function buildInvoice(
        ElectronicDocument $document,
        ElectronicBillingConfig $config,
        AmountToWordsService $amountToWords
    ): Invoice {
        $company = $document->company;
        $client = $document->client;

        if (! $company || ! $client) {
            throw ValidationException::withMessages([
                'document' => ['El documento electronico requiere empresa y cliente asociados.'],
            ]);
        }

        $currency = $document->currency_code ?: 'PEN';
        $payload = $document->payload ?? [];
        $correlative = (string) ($document->correlative ?: 1);
        $tax = (float) $document->tax_amount;
        $subtotal = (float) $document->subtotal_amount;
        $total = (float) $document->total_amount;
        $unitValue = $subtotal > 0 ? $subtotal : max($total - $tax, 0);
        $unitPrice = $total > 0 ? $total : $unitValue + $tax;
        $description = Arr::get(
            $payload,
            'meta.description',
            $document->transportService?->cargo_description
                ?: $document->quotation?->cargo_description
                ?: 'Servicio de transporte'
        );

        $invoice = (new Invoice())
            ->setUblVersion('2.1')
            ->setTipoOperacion('0101')
            ->setTipoDoc($document->document_type === 'invoice' ? '01' : '03')
            ->setSerie($document->series)
            ->setCorrelativo($correlative)
            ->setFechaEmision($document->issue_date)
            ->setFormaPago(new FormaPagoContado())
            ->setTipoMoneda($currency)
            ->setCompany($this->mapCompany($company, $config))
            ->setClient($this->mapClient($client))
            ->setMtoOperGravadas($subtotal)
            ->setMtoIGV($tax)
            ->setTotalImpuestos($tax)
            ->setValorVenta($subtotal)
            ->setSubTotal($total)
            ->setMtoImpVenta($total)
            ->setDetails($this->buildDetails($payload, $description, $unitValue, $tax, $unitPrice))
            ->setLegends([
                (new Legend())
                    ->setCode('1000')
                    ->setValue($amountToWords->toLegend($total, $currency)),
            ]);

        return $invoice;
    }

    private function mapCompany(LocalCompany $company, ElectronicBillingConfig $config): Company
    {
        return (new Company())
            ->setRuc($company->ruc)
            ->setRazonSocial($company->legal_name ?: $company->trade_name)
            ->setNombreComercial($company->trade_name)
            ->setAddress(
                (new Address())
                    ->setUbigueo($config->office_ubigeo)
                    ->setCodigoPais($config->office_country_code ?: 'PE')
                    ->setDepartamento($config->office_department)
                    ->setProvincia($config->office_province)
                    ->setDistrito($config->office_district)
                    ->setUrbanizacion($config->office_urbanization)
                    ->setDireccion($config->office_address)
            )
            ->setEmail($company->email)
            ->setTelephone($company->phone);
    }

    private function mapClient(LocalClient $client): Client
    {
        return (new Client())
            ->setTipoDoc($this->mapDocumentType($client->document_type))
            ->setNumDoc($client->document_number ?: '-')
            ->setRznSocial($client->business_name ?: $client->name ?: 'CLIENTE')
            ->setAddress(
                (new Address())
                    ->setDireccion($client->address)
                    ->setDistrito($client->district)
                    ->setProvincia($client->province)
                    ->setDepartamento($client->department)
                    ->setCodigoPais('PE')
            )
            ->setEmail($client->email)
            ->setTelephone($client->phone);
    }

    private function buildDetails(
        array $payload,
        string $defaultDescription,
        float $defaultUnitValue,
        float $defaultTax,
        float $defaultUnitPrice
    ): array {
        $lines = Arr::get($payload, 'lines', []);

        if (! is_array($lines) || $lines === []) {
            $lines = [[
                'code' => 'SERV-001',
                'sunat_code' => '78101803',
                'description' => $defaultDescription,
                'quantity' => 1,
                'unit_value' => $defaultUnitValue,
                'tax_amount' => $defaultTax,
                'unit_price' => $defaultUnitPrice,
            ]];
        }

        return array_map(function ($line) {
            $quantity = (float) ($line['quantity'] ?? 1);
            $unitValue = (float) ($line['unit_value'] ?? 0);
            $saleValue = (float) ($line['sale_value'] ?? ($unitValue * $quantity));
            $tax = (float) ($line['tax_amount'] ?? 0);
            $unitPrice = (float) ($line['unit_price'] ?? ($quantity > 0 ? ($saleValue + $tax) / $quantity : 0));

            return (new SaleDetail())
                ->setCodProducto($line['code'] ?? 'ITEM-001')
                ->setCodProdSunat($line['sunat_code'] ?? '78101803')
                ->setUnidad($line['unit_code'] ?? 'ZZ')
                ->setDescripcion($line['description'] ?? 'Servicio')
                ->setCantidad($quantity)
                ->setMtoValorUnitario($unitValue)
                ->setMtoValorVenta($saleValue)
                ->setMtoBaseIgv($saleValue)
                ->setPorcentajeIgv(18)
                ->setIgv($tax)
                ->setTipAfeIgv($tax > 0 ? '10' : '20')
                ->setTotalImpuestos($tax)
                ->setMtoPrecioUnitario($unitPrice);
        }, $lines);
    }

    private function mapDocumentType(?string $documentType): string
    {
        return match (strtoupper((string) $documentType)) {
            'DNI' => '1',
            'RUC' => '6',
            'CE' => '4',
            'PASSPORT', 'PASAPORTE' => '7',
            default => '0',
        };
    }
}
