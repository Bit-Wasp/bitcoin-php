<?php declare(strict_types=1);

namespace BitWasp\Bitcoin\Crypto\EcAdapter\Serializer\Key;

use BitWasp\Bitcoin\Crypto\EcAdapter\Key\PublicKeyInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\Key\XOnlyPublicKeyInterface;
use BitWasp\Buffertools\BufferInterface;

interface XOnlyPublicKeySerializerInterface
{
    /**
     * @param XOnlyPublicKeyInterface $publicKey
     * @return BufferInterface
     */
    public function serialize(XOnlyPublicKeyInterface $publicKey): BufferInterface;

    /**
     * @param BufferInterface $data
     * @return XOnlyPublicKeyInterface
     */
    public function parse(BufferInterface $data): XOnlyPublicKeyInterface;
}
