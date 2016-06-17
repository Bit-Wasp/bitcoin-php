<?php

namespace BitWasp\Bitcoin\Serializer\Key\HierarchicalKey;

use BitWasp\Bitcoin\Base58;
use BitWasp\Bitcoin\Key\Deterministic\HierarchicalKey;

class Base58ExtendedKeySerializer
{
    /**
     * @var ExtendedKeySerializer
     */
    private $serializer;

    /**
     * @param ExtendedKeySerializer $hexSerializer
     */
    public function __construct(ExtendedKeySerializer $hexSerializer)
    {
        $this->serializer = $hexSerializer;
    }

    /**
     * @param HierarchicalKey $key
     * @return string
     */
    public function serialize(HierarchicalKey $key)
    {
        return Base58::encodeCheck($this->serializer->serialize($key));
    }

    /**
     * @param string $base58
     * @return HierarchicalKey
     * @throws \BitWasp\Bitcoin\Exceptions\Base58ChecksumFailure
     */
    public function parse($base58)
    {
        return $this->serializer->parse(Base58::decodeCheck($base58));
    }
}
