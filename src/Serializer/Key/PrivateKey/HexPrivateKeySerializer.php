<?php

namespace BitWasp\Bitcoin\Serializer\Key\PrivateKey;

use BitWasp\Buffertools\Buffer;
use BitWasp\Bitcoin\Crypto\EcAdapter\EcAdapterInterface;
use BitWasp\Bitcoin\Key\PrivateKeyFactory;
use BitWasp\Buffertools\Parser;
use BitWasp\Bitcoin\Key\PrivateKey;
use BitWasp\Bitcoin\Key\PrivateKeyInterface;

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
        $multiplier = $privateKey->getSecretMultiplier();
        return Buffer::hex($this->ecAdapter->getMath()->decHex($multiplier), 32);
    }

    /**
     * @param Parser $parser
     * @return PrivateKey
     */
    public function fromParser(Parser & $parser)
    {
        $bytes = $parser->readBytes(32);
        return $this->parse($bytes->getHex());
    }
    /**
     * @param $string
     * @return PrivateKey
     */
    public function parse(Buffer $string)
    {
        $multiplier = $string->getInt();
        $privateKey = PrivateKeyFactory::fromInt($multiplier, false, $this->ecAdapter);
        return $privateKey;
    }
}
