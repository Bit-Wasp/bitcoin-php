<?php

namespace BitWasp\Bitcoin\Crypto\EcAdapter\Serializer\Key;

use BitWasp\Bitcoin\Crypto\EcAdapter\Key\PrivateKeyInterface;
use BitWasp\Buffertools\Buffer;

interface PrivateKeySerializerInterface
{
    /**
     * @param PrivateKeyInterface $privateKey
     * @return Buffer
     */
    public function serialize(PrivateKeyInterface $privateKey);

    /**
     * @return $this
     */
    public function setNextCompressed();

    /**
     * @param string|Buffer $data
     * @return PrivateKeyInterface
     */
    public function parse($data);
}
