<?php

namespace BitWasp\Bitcoin\Serializer\Key\PrivateKey;

use BitWasp\Bitcoin\Base58;
use BitWasp\Bitcoin\Buffer;
use BitWasp\Bitcoin\Exceptions\InvalidPrivateKey;
use BitWasp\Bitcoin\Exceptions\Base58ChecksumFailure;
use BitWasp\Bitcoin\Key\PrivateKey;
use BitWasp\Bitcoin\Key\PrivateKeyInterface;
use BitWasp\Bitcoin\Math\Math;
use BitWasp\Bitcoin\Network\NetworkInterface;

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
        $payload = Buffer::hex(Base58::decodeCheck($wif))->slice(1);
        $size = $payload->getSize();
        if (!in_array($size, [32, 33])) {
            throw new InvalidPrivateKey("Private key should be always be 32 or 33 bytes (depending on if it's compressed)");
        }

        return $this->hexSerializer->parse($payload->slice(0, 32)->getHex())
            ->setCompressed($size === 33);
    }
}
