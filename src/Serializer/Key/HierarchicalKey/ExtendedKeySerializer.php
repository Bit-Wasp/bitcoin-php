<?php

namespace BitWasp\Bitcoin\Serializer\Key\HierarchicalKey;

use BitWasp\Bitcoin\Crypto\EcAdapter\Adapter\EcAdapterInterface;
use BitWasp\Bitcoin\Key\Deterministic\HierarchicalKey;
use BitWasp\Bitcoin\Network\NetworkInterface;
use BitWasp\Bitcoin\Serializer\Key\HierarchicalKey\BasicKeyAdapter;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\BufferInterface;
use BitWasp\Buffertools\Exceptions\ParserOutOfRange;
use BitWasp\Buffertools\Parser;

class ExtendedKeySerializer
{
    /**
     * @var EcAdapterInterface
     */
    private $ecAdapter;

    /**
     * @var RawExtendedKeySerializer
     */
    private $rawSerializer;

    /**
     * @var BasicKeyAdapter
     */
    private $basicKey;

    /**
     * @param EcAdapterInterface $ecAdapter
     * @throws \Exception
     */
    public function __construct(EcAdapterInterface $ecAdapter)
    {
        $this->ecAdapter = $ecAdapter;
        $this->rawSerializer = new RawExtendedKeySerializer($ecAdapter);
        $this->basicKey = new BasicKeyAdapter();
    }

    /**
     * @param NetworkInterface $network
     * @param HierarchicalKey $key
     * @return Buffer
     */
    public function serialize(NetworkInterface $network, HierarchicalKey $key)
    {
        return $this->rawSerializer->serialize(
            $this->basicKey->getParams($network, $key)
        );
    }

    /**
     * @param NetworkInterface $network
     * @param Parser $parser
     * @return HierarchicalKey
     * @throws ParserOutOfRange
     */
    public function fromParser(NetworkInterface $network, Parser $parser)
    {
        return $this->basicKey->getKey(
            $network,
            $this->rawSerializer->fromParser($parser)
        );
    }

    /**
     * @param NetworkInterface $network
     * @param BufferInterface $buffer
     * @return HierarchicalKey
     */
    public function parse(NetworkInterface $network, BufferInterface $buffer)
    {
        return $this->fromParser($network, new Parser($buffer));
    }
}
