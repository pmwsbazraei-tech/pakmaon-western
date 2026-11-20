<?php

namespace App\Support;

/**
 * Adakah Bayar Online boleh digunakan sekarang?
 * CHIP perlu kunci lengkap; gateway simulasi hanya untuk local/testing.
 * Tanpa semakan ini, halaman pesanan cuba memuatkan gateway dan gagal (500) di production.
 */
class OnlinePayment
{
    public static function enabled(): bool
    {
        if (config('services.payment_driver') === 'chip') {
            return filled(config('services.chip.brand_id')) && filled(config('services.chip.secret_key'));
        }

        return app()->environment(['local', 'testing']);
    }
}
