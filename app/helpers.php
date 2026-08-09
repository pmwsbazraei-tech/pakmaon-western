<?php

if (! function_exists('rm')) {
    /**
     * Format nilai wang sebagai Ringgit Malaysia: RM1,234.56 (en-MY, 2 titik perpuluhan).
     * Padan dengan helper RM() prototaip.
     */
    function rm(int|float|string|null $n): string
    {
        $value = round((float) $n, 2);

        return 'RM'.number_format($value, 2, '.', ',');
    }
}
