<?php

namespace App\Support;

final class Money
{
    public static function format(?int $cents): string
    {
        $cents ??= 0;
        $sign = $cents < 0 ? '-' : '';

        return $sign.'RM'.number_format(abs($cents) / 100, 2, '.', ',');
    }

    /** "5.30" atau "RM 5.30" -> 530. Untuk input admin. */
    public static function toCents(string|float|int|null $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        $clean = preg_replace('/[^0-9.]/', '', (string) $value);

        return (int) round(((float) $clean) * 100);
    }
}
