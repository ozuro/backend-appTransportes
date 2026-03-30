<?php

namespace App\Services\Sunat;

use App\Models\ElectronicBillingConfig;
use App\Models\ElectronicDocument;
use Greenter\Model\Response\BillResult;
use Greenter\See;
use Greenter\Ws\Services\SunatEndpoints;
use Greenter\XMLSecLibs\Certificate\X509Certificate;
use Greenter\XMLSecLibs\Certificate\X509ContentType;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class GreenterSunatService
{
    public function __construct(
        private AmountToWordsService $amountToWords,
        private GreenterDocumentService $documentService
    ) {
    }

    public function generateSignedXml(ElectronicDocument $document, ElectronicBillingConfig $config): string
    {
        $see = $this->buildSee($document, $config);
        $invoice = $this->documentService->buildInvoice($document, $config, $this->amountToWords);
        $xml = $see->getXmlSigned($invoice);

        if (! $xml) {
            throw ValidationException::withMessages([
                'sunat' => ['No se pudo generar el XML firmado para el documento.'],
            ]);
        }

        return $xml;
    }

    public function send(ElectronicDocument $document, ElectronicBillingConfig $config): array
    {
        $see = $this->buildSee($document, $config);
        $invoice = $this->documentService->buildInvoice($document, $config, $this->amountToWords);
        $xml = $see->getXmlSigned($invoice);
        $result = $see->send($invoice);

        if (! $result instanceof BillResult) {
            throw ValidationException::withMessages([
                'sunat' => ['SUNAT devolvio una respuesta inesperada al emitir el documento.'],
            ]);
        }

        if (! $result->isSuccess()) {
            $error = $result->getError();
            throw ValidationException::withMessages([
                'sunat' => [trim(($error?->getCode() ?: '').' '.($error?->getMessage() ?: 'Error al enviar a SUNAT.'))],
            ]);
        }

        $documentNumber = sprintf(
            '%s-%08d',
            $document->series,
            $document->correlative ?: 1
        );
        $directory = 'sunat/'.$document->company_id.'/'.now()->format('Y/m');
        $xmlPath = $directory.'/'.$documentNumber.'.xml';
        $cdrPath = $directory.'/R-'.$documentNumber.'.zip';

        Storage::disk('local')->put($xmlPath, $xml);
        Storage::disk('local')->put($cdrPath, base64_decode((string) $result->getCdrZip()));

        return [
            'xml_path' => $xmlPath,
            'cdr_path' => $cdrPath,
            'cdr_response' => $result->getCdrResponse(),
        ];
    }

    private function buildSee(ElectronicDocument $document, ElectronicBillingConfig $config): See
    {
        $company = $document->company;

        if (! extension_loaded('soap')) {
            throw ValidationException::withMessages([
                'sunat' => [
                    'PHP SOAP no esta habilitado en el servidor del backend. Reinicia Apache o vuelve a levantar "php artisan serve" despues de activar ext-soap.',
                ],
            ]);
        }

        if (! $company || ! filled($company->ruc)) {
            throw ValidationException::withMessages([
                'company' => ['La empresa debe tener RUC antes de emitir con SUNAT.'],
            ]);
        }

        if (! filled($config->sol_user) || ! filled($config->sol_password)) {
            throw ValidationException::withMessages([
                'sunat' => ['Faltan credenciales SOL en la configuracion SUNAT.'],
            ]);
        }

        if (! filled($config->certificate_path) || ! is_file($config->certificate_path)) {
            throw ValidationException::withMessages([
                'sunat' => ['El certificado digital configurado no existe o no es accesible.'],
            ]);
        }

        $see = new See();
        $see->setCertificate($this->resolveCertificate($config));
        $see->setClaveSOL($company->ruc, $config->sol_user, $config->sol_password);
        $see->setService($this->resolveEndpoint($config));

        return $see;
    }

    private function resolveEndpoint(ElectronicBillingConfig $config): string
    {
        return $config->environment === 'production'
            ? SunatEndpoints::FE_PRODUCCION
            : SunatEndpoints::FE_BETA;
    }

    private function resolveCertificate(ElectronicBillingConfig $config): string
    {
        $path = $config->certificate_path;
        if (! $path || ! is_file($path)) {
            throw ValidationException::withMessages([
                'sunat' => ['El certificado digital configurado no existe o no es accesible.'],
            ]);
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $content = file_get_contents($path);

        if (in_array($extension, ['pfx', 'p12'], true)) {
            if (! filled($config->certificate_password)) {
                throw ValidationException::withMessages([
                    'sunat' => ['El certificado PFX/P12 requiere contraseña.'],
                ]);
            }

            $certificate = new X509Certificate($content, $config->certificate_password);

            return $certificate->export(X509ContentType::PEM);
        }

        return $content;
    }
}
