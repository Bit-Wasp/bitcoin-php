<?php

namespace BitWasp\Bitcoin\Crypto\EcAdapter\Serializer\Signature;

use BitWasp\Bitcoin\Crypto\EcAdapter\Signature\CompactSignatureInterface;
use BitWasp\Buffertools\Buffer;

interface CompactSignatureSerializerInterface
{
    /**
     * @param CompactSignatureInterface $signature
     * @return Buffer
     */
    public function serialize(CompactSignatureInterface $signature);

    /**
     * @param string|Buffer $data
     * @return CompactSignatureInterface
     */
    public function parse($data);
}
