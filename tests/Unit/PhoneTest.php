<?php

namespace Tests\Unit;

use App\Support\Money;
use App\Support\Phone;
use PHPUnit\Framework\TestCase;

class PhoneTest extends TestCase
{
    public function test_normalizes_malaysian_numbers(): void
    {
        foreach (['012-345 6789', '0123456789', '+60123456789', '60123456789', '123456789', '+60 12 345 6789'] as $in) {
            $this->assertSame('60123456789', Phone::normalize($in), $in);
        }
        $this->assertSame('601112345678', Phone::normalize('011-1234 5678'));
        $this->assertSame('6061234567', Phone::normalize('06-123 4567'));
        $this->assertNull(Phone::normalize('12345'));
        $this->assertNull(Phone::normalize(''));
        $this->assertSame('012-345 6789', Phone::display('60123456789'));
    }

    public function test_money(): void
    {
        $this->assertSame('RM5.30', Money::format(530));
        $this->assertSame('RM1,234.50', Money::format(123450));
        $this->assertSame(6255, Money::toCents('62.55'));
        $this->assertSame(530, Money::toCents('RM 5.30'));
    }
}
