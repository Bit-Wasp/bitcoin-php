<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Transaction\PSBT;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Crypto\EcAdapter\Adapter\EcAdapterInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\EcSerializer;
use BitWasp\Bitcoin\Crypto\EcAdapter\Key\PublicKeyInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\Serializer\Key\PublicKeySerializerInterface;
use BitWasp\Buffertools\BufferInterface;

class PSBTBip32Derivation
{
    /**
     * @var int
     */
    private $masterKeyFpr;

    /**
     * @var int[]
     */
    private $path;

    /**
     * @var BufferInterface
     */
    private $rawKey;

    /**
     * PSBTBip32Derivation constructor.
     * @param BufferInterface $rawKey
     * @param int $fpr
     * @param int ...$path
     */
    public function __construct(BufferInterface $rawKey, int $fpr, int ...$path)
    {
        $this->rawKey = $rawKey;
        $this->masterKeyFpr = $fpr;
        $this->path = $path;
    }

    /**
     * @return int[]
     */
    public function getPath(): array
    {
        return $this->path;
    }

    public function getMasterKeyFpr(): int
    {
        return $this->masterKeyFpr;
    }

    public function getRawPublicKey(): BufferInterface
    {
        return $this->rawKey;
    }

    public function getPublicKey(EcAdapterInterface $ecAdapter = null): PublicKeyInterface
    {
        $ecAdapter = $ecAdapter ?: Bitcoin::getEcAdapter();
        $pubKeySerializer = EcSerializer::getSerializer(PublicKeySerializerInterface::class, true, $ecAdapter);
        return $pubKeySerializer->parse($this->rawKey);
    }
}
