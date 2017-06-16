<?php

namespace BitWasp\Bitcoin\Crypto\EcAdapter\Impl\PhpEcc\Serializer\Key;

use BitWasp\Bitcoin\Crypto\EcAdapter\Impl\PhpEcc\Adapter\EcAdapter;
use BitWasp\Bitcoin\Crypto\EcAdapter\Impl\PhpEcc\Key\PrivateKey;
use BitWasp\Bitcoin\Crypto\EcAdapter\Key\PrivateKeyInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\Serializer\Key\PrivateKeySerializerInterface;
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
        return Buffer::int(gmp_strval($privateKey->getSecret(), 10), 32, $this->ecAdapter->getMath());
    }

    /**
     * @param Parser $parser
     * @return PrivateKey
     */
    public function fromParser(Parser $parser)
    {
        return $this->ecAdapter->getPrivateKey($parser->readBytes(32)->getGmp());
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
