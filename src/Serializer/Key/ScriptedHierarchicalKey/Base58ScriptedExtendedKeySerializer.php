<?php

namespace BitWasp\Bitcoin\Serializer\Key\ScriptedHierarchicalKey;

use BitWasp\Bitcoin\Base58;
use BitWasp\Bitcoin\Key\Deterministic\HierarchicalKeyScriptDecorator;
use BitWasp\Bitcoin\Network\NetworkInterface;

class Base58ScriptedExtendedKeySerializer
{
    /**
     * @var ExtendedKeyWithScriptSerializer
     */
    private $serializer;

    /**
     * @param ExtendedKeyWithScriptSerializer $hdSerializer
     */
    public function __construct(ExtendedKeyWithScriptSerializer $hdSerializer)
    {
        $this->serializer = $hdSerializer;
    }

    /**
     * @param NetworkInterface $network
     * @param HierarchicalKeyScriptDecorator $key
     * @return string
     */
    public function serialize(NetworkInterface $network, HierarchicalKeyScriptDecorator $key)
    {
        return Base58::encodeCheck($this->serializer->serialize($network, $key));
    }

    /**
     * @param NetworkInterface $network
     * @param string $base58
     * @return HierarchicalKeyScriptDecorator
     */
    public function parse(NetworkInterface $network, $base58)
    {
        return $this->serializer->parse($network, Base58::decodeCheck($base58));
    }
}
