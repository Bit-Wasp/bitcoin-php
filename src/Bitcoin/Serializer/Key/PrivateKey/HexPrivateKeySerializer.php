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
        $hex = str_pad($this->ecAdapter->getMath()->decHex($multiplier), 64, '0', STR_PAD_LEFT);
        $out = Buffer::hex($hex);
        return $out;
    }

    /**
     * @param Parser $parser
     * @return PrivateKey
     */
    public function fromParser(Parser & $parser)
    {
        $bytes = $parser->readBytes(32);
        return $this->parse($bytes->serialize('hex'));
    }
    /**
     * @param $string
     * @return PrivateKey
     */
    public function parse($string)
    {
        $multiplier = $this->ecAdapter->getMath()->hexDec($string);
        $privateKey = PrivateKeyFactory::fromInt($multiplier, false, $this->ecAdapter);
        return $privateKey;
    }
}
