<?php

namespace BitWasp\Bitcoin\Serializer\Key\PrivateKey;

use BitWasp\Buffertools\Buffer;
use BitWasp\Bitcoin\Crypto\EcAdapter\Adapter\EcAdapterInterface;
use BitWasp\Bitcoin\Key\PrivateKeyFactory;
use BitWasp\Buffertools\Parser;
use BitWasp\Bitcoin\Crypto\EcAdapter\Impl\PhpEcc\Key\PrivateKey;
use BitWasp\Bitcoin\Crypto\EcAdapter\Key\PrivateKeyInterface;

class HexPrivateKeySerializer
{
    /**
     * @var EcAdapterInterface
     */
    private $ecAdapter;

    /**
     * @param EcAdapterInterface $ecAdapter
     */
    public function __construct(EcAdapterInterface $ecAdapter)
    {
        $this->ecAdapter = $ecAdapter;
    }

    /**
     * @param PrivateKeyInterface $privateKey
     * @return Buffer
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
     * @param Parser $parser
     * @return PrivateKey
     */
    public function fromParser(Parser & $parser)
    {
        return PrivateKeyFactory::fromInt(
            $parser->readBytes(32)->getInt(),
            false,
            $this->ecAdapter
        );
    }

    /**
     * @param $string
     * @return PrivateKey
     */
    public function parse(Buffer $string)
    {
        return $this->fromParser(new Parser($string));
    }
}
