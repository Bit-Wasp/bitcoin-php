<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Signature;

use BitWasp\Bitcoin\Crypto\EcAdapter\Adapter\EcAdapterInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\EcSerializer;
use BitWasp\Bitcoin\Crypto\EcAdapter\Serializer\Signature\DerSignatureSerializerInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\Signature\SignatureInterface;
use BitWasp\Buffertools\BufferInterface;

class SignatureFactory
{

    /**
     * @param BufferInterface|string $string
     * @param EcAdapterInterface $ecAdapter
     * @return SignatureInterface
     */
    public static function fromHex($string, EcAdapterInterface $ecAdapter = null): SignatureInterface
    {
        /** @var DerSignatureSerializerInterface $serializer */
        $serializer = EcSerializer::getSerializer(DerSignatureSerializerInterface::class, true, $ecAdapter);
        return $serializer->parse($string);
    }
}
