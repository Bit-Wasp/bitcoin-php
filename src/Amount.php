<?php

namespace BitWasp\Bitcoin;

class Amount
{
    const COIN = 100000000;

    /**
     * @param int|string $satoshis
     * @return double
     */
    public function toBtc($satoshis)
    {
        return bcdiv((string)$satoshis, self::COIN, 8);
    }

    /**
     * @param double $double
     * @return int|string
     */
    public function toSatoshis($double)
    {
        return bcmul((float)$double, self::COIN, 0);
    }
}
