<?php

namespace Afk11\Bitcoin\Serializer\Key\HierarchicalKey;

use Afk11\Bitcoin\Network\NetworkInterface;
use Afk11\Bitcoin\Base58;
use Afk11\Bitcoin\Key\HierarchicalKey;

class ExtendedKeySerializer
{
    /**
     * @var HexExtendedKeySerializer
     */
    public $hexSerializer;

    /**
     * @param HexExtendedKeySerializer $hexSerializer
     */
    public function __construct(NetworkInterface $network, HexExtendedKeySerializer $hexSerializer)
    {
        $this->hexSerializer = $hexSerializer;
    }

    /**
     * @param HierarchicalKey $key
     * @return string
     */
    public function serialize(HierarchicalKey $key)
    {
        $bytes = $this->hexSerializer->serialize($key);
        $base58 = Base58::encodeCheck($bytes);
        return $base58;
    }

    /**
     * @param $base58
     * @return HierarchicalKey
     * @throws \Afk11\Bitcoin\Exceptions\Base58ChecksumFailure
     * @throws \Afk11\Bitcoin\Exceptions\ParserOutOfRange
     * @throws \Exception
     */
    public function parse($base58)
    {
        $payload = Base58::decodeCheck($base58);
        $hierarchicalKey = $this->hexSerializer->parse($payload);
        return $hierarchicalKey;
    }
}
