<?php

namespace BitWasp\Bitcoin\Crypto\EcAdapter\Impl\PhpEcc\Serializer\Key;

use BitWasp\Bitcoin\Crypto\EcAdapter\Impl\PhpEcc\Adapter\EcAdapter;
use BitWasp\Bitcoin\Crypto\EcAdapter\Serializer\Key\PrivateKeySerializerInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\Impl\PhpEcc\Key\PrivateKey;
use BitWasp\Bitcoin\Crypto\EcAdapter\Key\PrivateKeyInterface;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\BufferInterface;
use BitWasp\Buffertools\Parser;

class PrivateKeySerializer implements PrivateKeySerializerInterface
{
    /**
     * @var EcAdapter
     */
    private $ecAdapter;

    /**
     * @var bool
     */
    private $haveNextCompressed = false;

    /**
     * @param EcAdapter $ecAdapter
     */
    public function __construct(EcAdapter $ecAdapter)
    {
        $this->ecAdapter = $ecAdapter;
    }

    /**
     * @param PrivateKeyInterface $privateKey
     * @return BufferInterface
     */
    public function serialize(PrivateKeyInterface $privateKey)
    {
        return Buffer::int(
            $privateKey->getSecretMultiplier(),
            32,
            $this->ecAdapter->getMath()
        );
    }

    /**
     * @return $this
     */
    public function setNextCompressed()
    {
        $this->haveNextCompressed = true;
        return $this;
    }

    /**
     * @param Parser $parser
     * @return PrivateKey
     */
    public function fromParser(Parser $parser)
    {
        $compressed = $this->haveNextCompressed;
        $this->haveNextCompressed = false;
        return $this->ecAdapter->getPrivateKey($parser->readBytes(32)->getInt(), $compressed);
    }

    /**
     * @param BufferInterface|string $string
     * @return PrivateKey
     */
    public function parse($string)
    {
        return $this->fromParser(new Parser($string));
    }
}
