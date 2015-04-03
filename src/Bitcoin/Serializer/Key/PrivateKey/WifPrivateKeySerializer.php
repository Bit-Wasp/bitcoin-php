<?php

namespace BitWasp\Bitcoin\Serializer\Key\PrivateKey;

use BitWasp\Bitcoin\Base58;
use BitWasp\Buffertools\Buffer;
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
        $payload = Buffer::hex(
            $network->getPrivByte() .
            $this->hexSerializer->serialize($privateKey)->getHex() .
            ($privateKey->isCompressed() ? '01' : '')
        );

        return Base58::encodeCheck($payload);
    }

    /**
     * @param $wif
     * @return PrivateKey
     * @throws InvalidPrivateKey
     * @throws Base58ChecksumFailure
     */
    public function parse($wif)
    {
        $payload = Base58::decodeCheck($wif)->slice(1);
        $size = $payload->getSize();
        if (!in_array($size, [32, 33])) {
            throw new InvalidPrivateKey("Private key should be always be 32 or 33 bytes (depending on if it's compressed)");
        }

        return $this->hexSerializer->parse($payload->slice(0, 32))
            ->setCompressed($size === 33);
    }
}
