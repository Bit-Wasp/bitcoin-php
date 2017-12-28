<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Signature;

use BitWasp\Bitcoin\Crypto\EcAdapter\Adapter\EcAdapterInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\EcSerializer;
use BitWasp\Bitcoin\Crypto\EcAdapter\Serializer\Signature\DerSignatureSerializerInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\Signature\SignatureInterface;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\BufferInterface;

class SignatureFactory
{

    /**
     * @param string $string
     * @param EcAdapterInterface|null $ecAdapter
     * @return SignatureInterface
     * @throws \Exception
     */
    public static function fromHex(string $string, EcAdapterInterface $ecAdapter = null): SignatureInterface
    {
        return self::fromBuffer(Buffer::hex($string), $ecAdapter);
    }

    /**
     * @param BufferInterface $buffer
     * @param EcAdapterInterface|null $ecAdapter
     * @return SignatureInterface
     */
    public static function fromBuffer(BufferInterface $buffer, EcAdapterInterface $ecAdapter = null): SignatureInterface
    {
        /** @var DerSignatureSerializerInterface $serializer */
        $serializer = EcSerializer::getSerializer(DerSignatureSerializerInterface::class, true, $ecAdapter);
        return $serializer->parse($buffer);
    }
}
