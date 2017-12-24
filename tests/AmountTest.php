<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Tests;

use BitWasp\Bitcoin\Amount;

class AmountTest extends AbstractTestCase
{
    public function getVectors()
    {
        return [
            ['0.01000000', 1000000],
            ['1', Amount::COIN],
            ['1.12345678', 112345678],
            ['21000000', 2100000000000000],
            ['0', 0],
            ['0.0', 0]
        ];
    }

    /**
     * @param $btc
     * @param $satoshis
     * @dataProvider getVectors
     */
    public function testAmount(string $btc, int $satoshis)
    {
        $amount = new Amount();
        $this->assertEquals($btc, $amount->toBtc($satoshis));
        $this->assertEquals($satoshis, $amount->toSatoshis($btc));
    }

    public function testIgnoresLowValues()
    {
        $amount = new Amount();
        $value = '1.123456789';
        $expected = '112345678';
        $this->assertEquals($expected, ($amount->toSatoshis($value)));
    }
}
