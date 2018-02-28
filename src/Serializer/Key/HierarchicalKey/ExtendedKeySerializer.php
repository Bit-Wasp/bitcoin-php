<?php

namespace BitWasp\Bitcoin\Serializer\Key\HierarchicalKey;

use BitWasp\Bitcoin\Crypto\EcAdapter\Adapter\EcAdapterInterface;
use BitWasp\Bitcoin\Key\Deterministic\HierarchicalKey;
use BitWasp\Bitcoin\Key\PrivateKeyFactory;
use BitWasp\Bitcoin\Key\PublicKeyFactory;
use BitWasp\Bitcoin\Network\NetworkInterface;
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
     * @param EcAdapterInterface $ecAdapter
     * @throws \Exception
     */
    public function __construct(EcAdapterInterface $ecAdapter)
    {
        $this->ecAdapter = $ecAdapter;
        $this->rawSerializer = new RawExtendedKeySerializer($ecAdapter);
    }

    /**
     * @param NetworkInterface $network
     * @param HierarchicalKey $key
     * @return BufferInterface
     */
    public function serialize(NetworkInterface $network, HierarchicalKey $key)
    {
        if ($key->isPrivate()) {
            $prefix = $network->getHDPrivByte();
            $keyData = new Buffer("\x00" . $key->getPrivateKey()->getBinary());
        } else {
            $prefix = $network->getHDPubByte();
            $keyData = $key->getPublicKey()->getBuffer();
        }

        return $this->rawSerializer->serialize(
            new RawKeyParams(
                $prefix,
                $key->getDepth(),
                $key->getFingerprint(),
                $key->getSequence(),
                $key->getChainCode(),
                $keyData
            )
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
        $params = $this->rawSerializer->fromParser($parser);

        if ($params->getPrefix() === $network->getHDPubByte()) {
            $key = PublicKeyFactory::fromHex($params->getKeyData(), $this->ecAdapter);
        } else if ($params->getPrefix() === $network->getHDPrivByte()) {
            $key = PrivateKeyFactory::fromHex($params->getKeyData()->slice(1), true, $this->ecAdapter);
        } else {
            throw new \InvalidArgumentException('HD key magic bytes do not match network magic bytes');
        }

        return new HierarchicalKey(
            $this->ecAdapter,
            $params->getDepth(),
            $params->getFingerprint(),
            $params->getSequence(),
            $params->getChainCode(),
            $key
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
