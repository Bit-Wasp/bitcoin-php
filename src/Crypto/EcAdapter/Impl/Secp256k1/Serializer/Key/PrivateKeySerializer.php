<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Crypto\EcAdapter\Impl\Secp256k1\Serializer\Key;

use BitWasp\Bitcoin\Crypto\EcAdapter\Impl\Secp256k1\Adapter\EcAdapter;
use BitWasp\Bitcoin\Crypto\EcAdapter\Impl\Secp256k1\Key\PrivateKey;
use BitWasp\Bitcoin\Crypto\EcAdapter\Key\PrivateKeyInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\Serializer\Key\PrivateKeySerializerInterface;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\BufferInterface;
use BitWasp\Buffertools\Parser;

/**
 * Private Key Serializer - specific to secp256k1
 */
class PrivateKeySerializer implements PrivateKeySerializerInterface
{
    /**
     * @var EcAdapter
     */
    private $ecAdapter;

    /**
     * @param EcAdapter $ecAdapter
     */
    public function __construct(EcAdapter $ecAdapter)
    {
        $this->ecAdapter = $ecAdapter;
    }

    /**
     * @param PrivateKey $privateKey
     * @return BufferInterface
     */
    private function doSerialize(PrivateKey $privateKey): BufferInterface
    {
        return new Buffer($privateKey->getSecretBinary(), 32, $this->ecAdapter->getMath());
    }

    /**
     * @param PrivateKeyInterface $privateKey
     * @return BufferInterface
     */
    public function serialize(PrivateKeyInterface $privateKey): BufferInterface
    {
        /** @var PrivateKey $privateKey */
        return $this->doSerialize($privateKey);
    }

    /**
     * @param Parser $parser
     * @return PrivateKeyInterface
     * @throws \BitWasp\Buffertools\Exceptions\ParserOutOfRange
     */
    public function fromParser(Parser $parser): PrivateKeyInterface
    {
        return $this->ecAdapter->getPrivateKey($parser->readBytes(32)->getGmp());
    }

    /**
     * @param \BitWasp\Buffertools\BufferInterface|string $data
     * @return PrivateKeyInterface
     */
    public function parse($data): PrivateKeyInterface
    {
        return $this->fromParser(new Parser($data, $this->ecAdapter->getMath()));
    }
}
