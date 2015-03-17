<?php

namespace Afk11\Bitcoin\Network;

class NetworkFactory
{
    public static function bitcoin()
    {
        $network = new Network('00', '05', '80');
        $network
            ->setHDPubByte('0488b21e')
            ->setHDPrivByte('0488ade4')
            ->setNetMagicBytes('d9b4bef9');

        return $network;
    }

    public static function bitcoinTestnet()
    {
        $network = new Network('6f', 'c4', 'ef', true);
        $network
            ->setHDPubByte('043587cf')
            ->setHDPrivByte('04358394')
            ->setNetMagicBytes('d9b4bef9');

        return $network;
    }

    public static function litecoin()
    {
        $network = new Network('30', '05', 'b0');
        $network
            ->setHDPubByte('019da462')
            ->setHDPrivByte('019d9cfe')
            ->setNetMagicBytes('d9b4bef9');

        return $network;
    }

    public static function viacoin()
    {
        $network = new Network('47', '21', 'c7');
        $network
            ->setHDPubByte('0488b21e')
            ->setHDPrivByte('0488ade4')
            ->setNetMagicBytes('cbc6680f')
        ;

        return $network;
    }

    public static function viacoinTestnet()
    {
        $network = new Network('7f', 'c4', 'ff', true);
        $network
            ->setHDPubByte('043587cf')
            ->setHDPrivByte('04358394')
            ->setNetMagicBytes('cbc6680f')
        ;

        return $network;
    }
}
