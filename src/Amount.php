<?php

namespace BitWasp\Bitcoin;


class Amount
{
    /**
     * @param int|string $satoshis
     * @return double
     */
    public function toBTC($satoshis)
    {
        return bcdiv((string)$satoshis, 100000000, 8);
    }

    /**
     * @param double $double
     * @return int|string
     */
    public function toSatoshis($double)
    {
        return bcmul(sprintf("%.8f", (float)$double), 10000000, 0);
    }
}