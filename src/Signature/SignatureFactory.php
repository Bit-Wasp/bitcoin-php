<?php

namespace BitWasp\Bitcoin\Signature;

use BitWasp\Bitcoin\Crypto\EcAdapter\Adapter\EcAdapterInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\EcSerializer;
use BitWasp\Bitcoin\Crypto\EcAdapter\Serializer\Signature\DerSignatureSerializerInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\Signature\SignatureInterface;

class SignatureFactory
{

    /**
     * @param \BitWasp\Buffertools\BufferInterface|string $string
     * @param EcAdapterInterface $ecAdapter
     * @return SignatureInterface
     */
    public static function fromHex($string, EcAdapterInterface $ecAdapter = null)
    {
        /** @var DerSignatureSerializerInterface $serializer */
        $serializer = EcSerializer::getSerializer(DerSignatureSerializerInterface::class, true, $ecAdapter);
        return $serializer->parse($string);
    }
}
