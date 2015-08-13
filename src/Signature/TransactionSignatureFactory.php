<?php

namespace BitWasp\Bitcoin\Signature;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Crypto\EcAdapter\Adapter\EcAdapterInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\EcSerializer;
use BitWasp\Bitcoin\Math\Math;
use BitWasp\Bitcoin\Serializer\Signature\DerSignatureSerializer;
use BitWasp\Bitcoin\Serializer\Signature\TransactionSignatureSerializer;

use BitWasp\Bitcoin\Crypto\EcAdapter\Serializer\Signature\DerSignatureSerializerInterface;

class TransactionSignatureFactory
{
    /**
     * @param $string
     * @param EcAdapterInterface $ecAdapter
     * @return TransactionSignatureInterface
     */
    public static function fromHex($string, EcAdapterInterface $ecAdapter = null)
    {
        $ecAdapter = $ecAdapter ?: Bitcoin::getEcAdapter();

        $serializer = new TransactionSignatureSerializer(
            EcSerializer::getSerializer(
                $ecAdapter,
                'BitWasp\Bitcoin\Crypto\EcAdapter\Serializer\Signature\DerSignatureSerializerInterface'
            )
        );

        return $serializer->parse($string);
    }
}
