<?php

namespace Afk11\Bitcoin\Serializer\Key\PrivateKey;

use Afk11\Bitcoin\Base58;
use Afk11\Bitcoin\Exceptions\InvalidPrivateKey;
use Afk11\Bitcoin\Exceptions\Base58ChecksumFailure;
use Afk11\Bitcoin\Key\PrivateKey;
use Afk11\Bitcoin\Key\PrivateKeyInterface;
use Afk11\Bitcoin\Math\Math;
use Afk11\Bitcoin\NetworkInterface;

class WifPrivateKeySerializer
{
    /**
     * @var Math
     */
    private $math;

    /**
     * @var HexPrivateKeySerializer
     */
    private $hexSerializer;

    /**
     * @param Math $math
     * @param HexPrivateKeySerializer $hexSerializer
     */
    public function __construct(Math $math, HexPrivateKeySerializer $hexSerializer)
    {
        $this->math = $math;
        $this->hexSerializer = $hexSerializer;
    }

    /**
     * @param NetworkInterface $network
     * @param PrivateKeyInterface $privateKey
     * @return string
     */
    public function serialize(NetworkInterface $network, PrivateKeyInterface $privateKey)
    {
        $hex = $this->hexSerializer->serialize($privateKey)->serialize('hex');

        $payload = sprintf(
            "%s%s%s",
            $network->getPrivByte(),
            $hex,
            ($privateKey->isCompressed() ? '01' : '')
        );

        $wif = Base58::encodeCheck($payload);

        return $wif;
    }

    /**
     * @param $wif
     * @return PrivateKey
     * @throws InvalidPrivateKey
     * @throws Base58ChecksumFailure
     */
    public function parse($wif)
    {
        // [2 bytes, <either 32 or 33>, 4 bytes
        $payload = Base58::decodeCheck($wif);

        $hex = substr($payload, 2);
        $hexLen = strlen($hex);

        if ($hexLen !== 64 && $hexLen !== 66) {
            throw new InvalidPrivateKey("Private key should be always be 32 or 33 bytes (depending on if it's compressed)");
        }

        $key = substr($hex, 0, 64);
        $private = $this->hexSerializer->parse($key);
        $private->setCompressed(strlen($hex) === 66);

        return $private;
    }
}
