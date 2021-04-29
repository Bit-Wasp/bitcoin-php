<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Network;

class NetworkFactory
{
    /**
     * @return NetworkInterface
     * @throws \Exception
     */
    public static function bitcoin(): NetworkInterface
    {
        return new Networks\Bitcoin();
    }

    /**
     * @return NetworkInterface
     * @throws \Exception
     */
    public static function bitcoinTestnet(): NetworkInterface
    {
        return new Networks\BitcoinTestnet();
    }

    /**
     * @return NetworkInterface
     * @throws \Exception
     */
    public static function bitcoinRegtest(): NetworkInterface
    {
        return new Networks\BitcoinRegtest();
    }
}
