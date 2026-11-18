<?php

namespace App\Support;

final class Phone
{
    /**
     * Seragamkan nombor telefon Malaysia kepada 60XXXXXXXXX.
     * Terima: 012-345 6789, 0123456789, +60123456789, 60123456789, 123456789.
     * Pulangkan null jika bukan nombor Malaysia yang sah.
     */
    public static function normalize(?string $input): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $input);
        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '60')) {
            $local = substr($digits, 2);
        } elseif (str_starts_with($digits, '0')) {
            $local = substr($digits, 1);
        } else {
            $local = $digits;
        }

        // Mudah alih: 1X + 7-8 digit; talian tetap: 3-9 + 7-8 digit.
        if (! preg_match('/^(1\d{8,9}|[3-9]\d{7,8})$/', $local)) {
            return null;
        }

        return '60'.$local;
    }

    public static function display(?string $normalized): string
    {
        if (! $normalized) {
            return '';
        }
        $local = '0'.substr($normalized, 2);

        return preg_replace('/^(01\d)(\d{3,4})(\d{4})$/', '$1-$2 $3', $local) ?? $local;
    }
}
