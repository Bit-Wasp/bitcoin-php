<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin;

class Amount
{
    const COIN = 100000000;

    /**
     * @param int|string $satoshis
     * @return string
     */
    public function toBtc(int $satoshis): string
    {
        return bcdiv((string)$satoshis, (string) self::COIN, 8);
    }

    /**
     * @param string $double
     * @return int
     */
    public function toSatoshis(string $double): int
    {
        return (int) bcmul((string) $double, (string) self::COIN, 0);
    }
}
