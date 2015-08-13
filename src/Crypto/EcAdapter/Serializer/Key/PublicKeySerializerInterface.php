<?php

namespace BitWasp\Bitcoin\Crypto\EcAdapter\Serializer\Key;

use BitWasp\Bitcoin\Crypto\EcAdapter\Key\PublicKeyInterface;
use BitWasp\Buffertools\Buffer;

interface PublicKeySerializerInterface
{
    /**
     * @param PublicKeyInterface $publicKey
     * @return Buffer
     */
    public function serialize(PublicKeyInterface $publicKey);

    /**
     * @param string|Buffer $data
     * @return PublicKeyInterface
     */
    public function parse($data);
}
