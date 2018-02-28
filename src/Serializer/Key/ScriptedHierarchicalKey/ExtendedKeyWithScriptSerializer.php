<?php


namespace BitWasp\Bitcoin\Serializer\Key\ScriptedHierarchicalKey;

use BitWasp\Bitcoin\Crypto\EcAdapter\Adapter\EcAdapterInterface;
use BitWasp\Bitcoin\Key\Deterministic\HierarchicalKey;
use BitWasp\Bitcoin\Key\Deterministic\HierarchicalKeyScriptDecorator;
use BitWasp\Bitcoin\Key\PrivateKeyFactory;
use BitWasp\Bitcoin\Key\PublicKeyFactory;
use BitWasp\Bitcoin\Network\NetworkInterface;
use BitWasp\Bitcoin\Serializer\Key\HierarchicalKey\RawExtendedKeySerializer;
use BitWasp\Bitcoin\Serializer\Key\HierarchicalKey\RawKeyParams;
use BitWasp\Buffertools\Buffer;
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
     * @var GlobalHdKeyPrefixConfig
     */
    private $config;

    /**
     * ExtendedKeyWithScriptSerializer constructor.
     * @param EcAdapterInterface $ecAdapter
     * @param GlobalHdKeyPrefixConfig $hdPrefixConfig
     */
    public function __construct(EcAdapterInterface $ecAdapter, GlobalHdKeyPrefixConfig $hdPrefixConfig)
    {
        $this->ecAdapter = $ecAdapter;
        $this->rawSerializer = new RawExtendedKeySerializer($ecAdapter);
        $this->config = $hdPrefixConfig;
    }

    /**
     * @param NetworkInterface $network
     * @param HierarchicalKeyScriptDecorator $key
     * @return BufferInterface
     * @throws \Exception
     */
    public function serialize(NetworkInterface $network, HierarchicalKeyScriptDecorator $key)
    {
        $scriptConfig = $this->config
            ->getNetworkHdPrefixConfig($network)
            ->getConfigForScriptType($key->getScriptDataFactory()->getScriptType())
        ;

        $hdKey = $key->getHdKey();
        if ($hdKey->isPrivate()) {
            $prefix = $scriptConfig->getPrivatePrefix();
            $keyData = new Buffer("\x00" . $hdKey->getPrivateKey()->getBinary());
        } else {
            $prefix = $scriptConfig->getPublicPrefix();
            $keyData = $hdKey->getPublicKey()->getBuffer();
        }

        return $this->rawSerializer->serialize(new RawKeyParams(
            $prefix,
            $hdKey->getDepth(),
            $hdKey->getFingerprint(),
            $hdKey->getSequence(),
            $hdKey->getChainCode(),
            $keyData
        ));
    }

    /**
     * @param NetworkInterface $network
     * @param Parser $parser
     * @return HierarchicalKeyScriptDecorator
     * @throws ParserOutOfRange
     * @throws \Exception
     */
    public function fromParser(NetworkInterface $network, Parser $parser)
    {
        $params = $this->rawSerializer->fromParser($parser);
        $scriptConfig = $this->config
            ->getNetworkHdPrefixConfig($network)
            ->getConfigForPrefix($params->getPrefix())
        ;

        if ($params->getPrefix() === $scriptConfig->getPublicPrefix()) {
            $key = PublicKeyFactory::fromHex($params->getKeyData(), $this->ecAdapter);
        } else if ($params->getPrefix() === $scriptConfig->getPrivatePrefix()) {
            $key = PrivateKeyFactory::fromHex($params->getKeyData()->slice(1), true, $this->ecAdapter);
        } else {
            throw new \InvalidArgumentException('Invalid prefix for extended key');
        }

        return new HierarchicalKeyScriptDecorator(
            $scriptConfig->getScriptDataFactory(),
            new HierarchicalKey(
                $this->ecAdapter,
                $params->getDepth(),
                $params->getFingerprint(),
                $params->getSequence(),
                $params->getChainCode(),
                $key
            )
        );
    }

    /**
     * @param NetworkInterface $network
     * @param BufferInterface $buffer
     * @return HierarchicalKeyScriptDecorator
     * @throws ParserOutOfRange
     */
    public function parse(NetworkInterface $network, BufferInterface $buffer)
    {
        return $this->fromParser($network, new Parser($buffer));
    }
}
