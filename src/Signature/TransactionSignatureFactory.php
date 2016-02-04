<?php

namespace BitWasp\Bitcoin\Signature;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Crypto\EcAdapter\Adapter\EcAdapterInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\EcSerializer;
use BitWasp\Bitcoin\Serializer\Signature\TransactionSignatureSerializer;

class TransactionSignatureFactory
{
    /**
     * @param \BitWasp\Buffertools\BufferInterface|string $string
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
