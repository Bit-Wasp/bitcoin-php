<?php

namespace BitWasp\Bitcoin\Network;

class NetworkFactory
{
    /**
     * @param string $address
     * @param string $p2sh
     * @param string $privateKey
     * @param bool $testnet
     * @return Network
     * @throws \Exception
     */
    public static function create($address, $p2sh, $privateKey, $testnet = false)
    {
        return new Network($address, $p2sh, $privateKey, $testnet);
    }

    /**
     * @return NetworkInterface
     * @throws \Exception
     */
    public static function bitcoin()
    {
        $network = self::create('00', '05', '80')
            ->setP2WPKHByte('06')
            ->setP2WSHByte('0a')
            ->setHDPubByte('0488b21e')
            ->setHDPrivByte('0488ade4')
            ->setNetMagicBytes('d9b4bef9');

        return $network;
    }

    /**
     * @return NetworkInterface
     * @throws \Exception
     */
    public static function bitcoinTestnet()
    {
        $network = self::create('6f', 'c4', 'ef', true)
            ->setP2WPKHByte('03')
            ->setP2WSHByte('28')
            ->setHDPubByte('043587cf')
            ->setHDPrivByte('04358394')
            ->setNetMagicBytes('0709110b');

        return $network;
    }

    /**
     * @return NetworkInterface
     */
    public static function bitcoinSegnet()
    {
        $network = self::create('1e', '32', '9e', true)
            ->setHDPubByte('043587cf')
            ->setHDPrivByte('04358394')
            ->setNetMagicBytes('0709110b');

        return $network;
    }

    /**
     * @return NetworkInterface
     * @throws \Exception
     */
    public static function litecoin()
    {
        $network = self::create('30', '05', 'b0')
            ->setHDPubByte('019da462')
            ->setHDPrivByte('019d9cfe')
            ->setNetMagicBytes('dbb6c0fb');

        return $network;
    }

    /**
     * @return NetworkInterface
     * @throws \Exception
     */
    public static function litecoinTestnet()
    {
        $network = self::create('6f', 'c4', 'ef', true)
            ->setHDPubByte('019da462')
            ->setHDPrivByte('019d9cfe')
            ->setNetMagicBytes('dcb7c1fc');

        return $network;
    }

    /**
     * @return NetworkInterface
     * @throws \Exception
     */
    public static function viacoin()
    {
        $network = self::create('47', '21', 'c7')
            ->setHDPubByte('0488b21e')
            ->setHDPrivByte('0488ade4')
            ->setNetMagicBytes('cbc6680f')
        ;

        return $network;
    }

    /**
     * @return NetworkInterface
     * @throws \Exception
     */
    public static function viacoinTestnet()
    {
        $network = self::create('7f', 'c4', 'ff', true)
            ->setHDPubByte('043587cf')
            ->setHDPrivByte('04358394')
            ->setNetMagicBytes('92efc5a9')
        ;

        return $network;
    }

    /**
     * @return NetworkInterface
     * @throws \Exception
     */
    public static function dogecoin()
    {
        $network = self::create('1e', '16', '9e')
            ->setHDPubByte('02facafd')
            ->setHDPrivByte('02fac398')
            ->setNetMagicBytes('c0c0c0c0');

        return $network;
    }

    /**
     * @return NetworkInterface
     * @throws \Exception
     */
    public static function dogecoinTestnet()
    {
        $network = self::create('71', 'c4', 'f1', true)
            ->setHDPubByte('043587cf')
            ->setHDPrivByte('0432a243')
            ->setNetMagicBytes('c0c0c0c0');

        return $network;
    }

    /**
     * @return NetworkInterface
     * @throws \Exception
     */
    public static function startcoin()
    {
        $network = self::create('7d', 'fd', '05')
            ->setHDPubByte('0488b21e')
            ->setHDPrivByte('0488ade4')
            ->setNetMagicBytes('ffc4badf');

        return $network;
    }

    /**
     * @return NetworkInterface
     * @throws \Exception
     */
    public static function startcoinTestnet()
    {
        $network = self::create('7f', 'f4', 'c4', true)
            ->setHDPubByte('043587cf')
            ->setHDPrivByte('04468394')
            ->setNetMagicBytes('ffc4b9de');

        return $network;
    }

    /**
     * @return NetworkInterface
     * @throws \Exception
     */
    public static function dash()
    {
        $network = self::create('4c', '10', 'cc')
            ->setHDPubByte('02fe52f8')
            ->setHDPrivByte('02fe52cc')
            ->setNetMagicBytes('bd6b0cbf');

        return $network;
    }


    /**
     * @return NetworkInterface
     * @throws \Exception
     */
    public static function dashTestnet()
    {
        $network = self::create('8b', '13', 'ef', true)
            ->setHDPubByte('3a8061a0')
            ->setHDPrivByte('3a805837')
            ->setNetMagicBytes('ffcae2ce');

        return $network;
    }
}
