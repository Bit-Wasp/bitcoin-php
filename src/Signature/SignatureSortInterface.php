<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Signature;

use BitWasp\Buffertools\BufferInterface;

interface SignatureSortInterface
{
    /**
     * @param \BitWasp\Bitcoin\Crypto\EcAdapter\Signature\SignatureInterface[] $signatures
     * @param \BitWasp\Bitcoin\Crypto\EcAdapter\Key\PublicKeyInterface[] $publicKeys
     * @param BufferInterface $messageHash
     * @return \SplObjectStorage
     */
    public function link(array $signatures, array $publicKeys, BufferInterface $messageHash): \SplObjectStorage;
}
