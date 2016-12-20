<?php

namespace BitWasp\Bitcoin\Crypto\EcAdapter\Serializer\Key;

use BitWasp\Bitcoin\Crypto\EcAdapter\Key\PrivateKeyInterface;
use BitWasp\Buffertools\BufferInterface;

interface PrivateKeySerializerInterface
{
    /**
     * @param PrivateKeyInterface $privateKey
     * @return BufferInterface
     */
    public function serialize(PrivateKeyInterface $privateKey);

    /**
     * @param string|BufferInterface $data
     * @return PrivateKeyInterface
     */
    public function parse($data);
}
