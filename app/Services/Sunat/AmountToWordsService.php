<?php

namespace App\Services\Sunat;

class AmountToWordsService
{
    public function toLegend(float $amount, string $currencyCode = 'PEN'): string
    {
        $integerPart = (int) floor($amount);
        $decimalPart = (int) round(($amount - $integerPart) * 100);
        $currencyLabel = strtoupper($currencyCode) === 'USD' ? 'DOLARES' : 'SOLES';

        return sprintf(
            'SON %s CON %02d/100 %s',
            $this->convertNumber($integerPart),
            $decimalPart,
            $currencyLabel
        );
    }

    private function convertNumber(int $number): string
    {
        if ($number === 0) {
            return 'CERO';
        }

        if ($number < 0) {
            return 'MENOS '.$this->convertNumber(abs($number));
        }

        $parts = [];

        if ($number >= 1000000) {
            $millions = intdiv($number, 1000000);
            $parts[] = $millions === 1
                ? 'UN MILLON'
                : $this->convertHundreds($millions).' MILLONES';
            $number %= 1000000;
        }

        if ($number >= 1000) {
            $thousands = intdiv($number, 1000);
            $parts[] = $thousands === 1
                ? 'MIL'
                : $this->convertHundreds($thousands).' MIL';
            $number %= 1000;
        }

        if ($number > 0) {
            $parts[] = $this->convertHundreds($number);
        }

        return implode(' ', array_filter($parts));
    }

    private function convertHundreds(int $number): string
    {
        $units = [
            '',
            'UNO',
            'DOS',
            'TRES',
            'CUATRO',
            'CINCO',
            'SEIS',
            'SIETE',
            'OCHO',
            'NUEVE',
            'DIEZ',
            'ONCE',
            'DOCE',
            'TRECE',
            'CATORCE',
            'QUINCE',
            'DIECISEIS',
            'DIECISIETE',
            'DIECIOCHO',
            'DIECINUEVE',
            'VEINTE',
            'VEINTIUNO',
            'VEINTIDOS',
            'VEINTITRES',
            'VEINTICUATRO',
            'VEINTICINCO',
            'VEINTISEIS',
            'VEINTISIETE',
            'VEINTIOCHO',
            'VEINTINUEVE',
        ];

        $tens = [
            30 => 'TREINTA',
            40 => 'CUARENTA',
            50 => 'CINCUENTA',
            60 => 'SESENTA',
            70 => 'SETENTA',
            80 => 'OCHENTA',
            90 => 'NOVENTA',
        ];

        $hundreds = [
            100 => 'CIEN',
            200 => 'DOSCIENTOS',
            300 => 'TRESCIENTOS',
            400 => 'CUATROCIENTOS',
            500 => 'QUINIENTOS',
            600 => 'SEISCIENTOS',
            700 => 'SETECIENTOS',
            800 => 'OCHOCIENTOS',
            900 => 'NOVECIENTOS',
        ];

        if ($number < 30) {
            return $units[$number];
        }

        if ($number < 100) {
            $base = intdiv($number, 10) * 10;
            $rest = $number % 10;

            return $rest === 0
                ? $tens[$base]
                : $tens[$base].' Y '.$units[$rest];
        }

        if ($number === 100) {
            return 'CIEN';
        }

        $base = intdiv($number, 100) * 100;
        $rest = $number % 100;
        $prefix = $base === 100 ? 'CIENTO' : $hundreds[$base];

        return $rest === 0
            ? $prefix
            : $prefix.' '.$this->convertHundreds($rest);
    }
}
