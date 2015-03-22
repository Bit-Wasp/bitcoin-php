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
        foreach ([$address, $p2sh, $privateKey] as $byte) {
            if (strlen($byte) !== 2 || preg_match('/^[0-9A-Fa-f]{2}_*/', $byte) === false) {
                throw new \Exception('Network bytes must be two letters of hexadecimal (check padding?)');
            }
        }

        return new Network($address, $p2sh, $privateKey, $testnet);
    }

    public static function bitcoin()
    {
        $network = self::create('00', '05', '80')
            ->setHDPubByte('0488b21e')
            ->setHDPrivByte('0488ade4')
            ->setNetMagicBytes('d9b4bef9');

        return $network;
    }

    public static function bitcoinTestnet()
    {
        $network = self::create('6f', 'c4', 'ef', true)
            ->setHDPubByte('043587cf')
            ->setHDPrivByte('04358394')
            ->setNetMagicBytes('d9b4bef9');

        return $network;
    }

    public static function litecoin()
    {
        $network = self::create('30', '05', 'b0')
            ->setHDPubByte('019da462')
            ->setHDPrivByte('019d9cfe')
            ->setNetMagicBytes('d9b4bef9');

        return $network;
    }

    public static function viacoin()
    {
        $network = self::create('47', '21', 'c7')
            ->setHDPubByte('0488b21e')
            ->setHDPrivByte('0488ade4')
            ->setNetMagicBytes('cbc6680f')
        ;

        return $network;
    }

    public static function viacoinTestnet()
    {
        $network = self::create('7f', 'c4', 'ff', true)
            ->setHDPubByte('043587cf')
            ->setHDPrivByte('04358394')
            ->setNetMagicBytes('cbc6680f')
        ;

        return $network;
    }
}
