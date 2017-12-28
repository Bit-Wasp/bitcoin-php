<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Crypto\EcAdapter\Serializer\Signature;

use BitWasp\Bitcoin\Crypto\EcAdapter\Signature\CompactSignatureInterface;
use BitWasp\Buffertools\BufferInterface;

interface CompactSignatureSerializerInterface
{
    /**
     * @param CompactSignatureInterface $signature
     * @return BufferInterface
     */
    public function serialize(CompactSignatureInterface $signature): BufferInterface;

    /**
     * @param BufferInterface $data
     * @return CompactSignatureInterface
     */
    public function parse(BufferInterface $data): CompactSignatureInterface;
}
