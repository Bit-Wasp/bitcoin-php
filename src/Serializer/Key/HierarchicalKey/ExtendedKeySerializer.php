<?php

namespace BitWasp\Bitcoin\Serializer\Key\HierarchicalKey;

use BitWasp\Bitcoin\Base58;
use BitWasp\Bitcoin\Key\Deterministic\HierarchicalKey;

class ExtendedKeySerializer
{
    /**
     * @var HexExtendedKeySerializer
     */
    public $hexSerializer;

    /**
     * @param HexExtendedKeySerializer $hexSerializer
     */
    public function __construct(HexExtendedKeySerializer $hexSerializer)
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
        return Base58::encodeCheck($bytes);
    }

    /**
     * @param string $base58
     * @return HierarchicalKey
     * @throws \BitWasp\Bitcoin\Exceptions\Base58ChecksumFailure
     * @throws \BitWasp\Buffertools\Exceptions\ParserOutOfRange
     * @throws \Exception
     */
    public function parse($base58)
    {
        $payload = Base58::decodeCheck($base58);
        return $this->hexSerializer->parse($payload);
    }
}
