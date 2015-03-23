<?php

namespace BitWasp\Bitcoin\Serializer\Key\PrivateKey;

use BitWasp\Bitcoin\Buffer;
use BitWasp\Bitcoin\Crypto\EcAdapter\EcAdapterInterface;
use BitWasp\Bitcoin\Parser;
use BitWasp\Bitcoin\Key\PrivateKey;
use BitWasp\Bitcoin\Key\PrivateKeyInterface;
use BitWasp\Bitcoin\Math\Math;
use Mdanter\Ecc\GeneratorPoint;

class HexPrivateKeySerializer
{
    /**
     * @var EcAdapterInterface
     */
    private $ecAdapter;

    /**
     * @param Math $math
     * @param GeneratorPoint $G
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
        $privateKey = new PrivateKey($this->ecAdapter, $multiplier, false);
        return $privateKey;
    }
}
