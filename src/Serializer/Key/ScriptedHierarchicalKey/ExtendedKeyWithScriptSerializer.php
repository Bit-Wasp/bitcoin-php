<?php


namespace BitWasp\Bitcoin\Serializer\Key\ScriptedHierarchicalKey;

use BitWasp\Bitcoin\Crypto\EcAdapter\Adapter\EcAdapterInterface;
use BitWasp\Bitcoin\Key\Deterministic\ScriptedHierarchicalKey;
use BitWasp\Bitcoin\Network\NetworkInterface;
use BitWasp\Bitcoin\Serializer\Key\HierarchicalKey\RawExtendedKeySerializer;
use BitWasp\Buffertools\BufferInterface;
use BitWasp\Buffertools\Exceptions\ParserOutOfRange;
use BitWasp\Buffertools\Parser;

class ExtendedKeyWithScriptSerializer
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
     * @var KeyWithScriptAdapter
     */
    private $hdKeyWithScript;

    /**
     * ExtendedKeyWithScriptSerializer constructor.
     * @param EcAdapterInterface $ecAdapter
     * @param GlobalHdKeyPrefixConfig $hdPrefixConfig
     */
    public function __construct(EcAdapterInterface $ecAdapter, GlobalHdKeyPrefixConfig $hdPrefixConfig)
    {
        $this->ecAdapter = $ecAdapter;
        $this->rawSerializer = new RawExtendedKeySerializer($ecAdapter);
        $this->hdKeyWithScript = new KeyWithScriptAdapter($hdPrefixConfig);
    }

    /**
     * @param NetworkInterface $network
     * @param ScriptedHierarchicalKey $key
     * @return BufferInterface
     */
    public function serialize(NetworkInterface $network, ScriptedHierarchicalKey $key)
    {
        return $this->rawSerializer->serialize($this->hdKeyWithScript->getParams($network, $key));
    }

    /**
     * @param NetworkInterface $network
     * @param Parser $parser
     * @return ScriptedHierarchicalKey
     * @throws ParserOutOfRange
     */
    public function fromParser(NetworkInterface $network, Parser $parser)
    {
        return $this->hdKeyWithScript->getKey($network, $this->rawSerializer->fromParser($parser));
    }

    /**
     * @param NetworkInterface $network
     * @param BufferInterface $buffer
     * @return ScriptedHierarchicalKey
     */
    public function parse(NetworkInterface $network, BufferInterface $buffer)
    {
        return $this->fromParser($network, new Parser($buffer));
    }
}
