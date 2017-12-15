<?php

namespace BitWasp\Bitcoin\Network;

class NetworkFactory
{
    /**
     * @return NetworkInterface
     * @throws \Exception
     */
    public static function bitcoin()
    {
        return new Networks\Bitcoin();
    }

    /**
     * @return NetworkInterface
     * @throws \Exception
     */
    public static function bitcoinTestnet()
    {
        return new Networks\BitcoinTestnet();
    }

    /**
     * @return NetworkInterface
     * @throws \Exception
     */
    public static function bitcoinRegtest()
    {
        return new Networks\BitcoinRegtest();
    }

    /**
     * @return Networks\Litecoin
     */
    public static function litecoin()
    {
        return new Networks\Litecoin();
    }

    /**
     * @return Networks\LitecoinTestnet
     */
    public static function litecoinTestnet()
    {
        return new Networks\LitecoinTestnet();
    }

    /**
     * @return Networks\Viacoin
     */
    public static function viacoin()
    {
        return new Networks\Viacoin();
    }

    /**
     * @return Networks\ViacoinTestnet
     */
    public static function viacoinTestnet()
    {
        return new Networks\ViacoinTestnet();
    }

    /**
     * @return Networks\Dogecoin
     */
    public static function dogecoin()
    {
        return new Networks\Dogecoin();
    }

    /**
     * @return Networks\DogecoinTestnet
     */
    public static function dogecoinTestnet()
    {
        return new Networks\DogecoinTestnet();
    }

    /**
     * @return Networks\Dash
     */
    public static function dash()
    {
        return new Networks\Dash();
    }

    /**
     * @return Networks\DashTestnet
     */
    public static function dashTestnet()
    {
        return new Networks\DashTestnet();
    }
}
