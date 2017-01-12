<?php


namespace BitWasp\Bitcoin\Serializer\Key\HierarchicalKey;

use BitWasp\Buffertools\Buffer;
use BitWasp\Bitcoin\Crypto\EcAdapter\Adapter\EcAdapterInterface;
use BitWasp\Buffertools\BufferInterface;
use BitWasp\Buffertools\Exceptions\ParserOutOfRange;
use BitWasp\Bitcoin\Key\PrivateKeyFactory;
use BitWasp\Bitcoin\Key\PublicKeyFactory;
use BitWasp\Bitcoin\Network\NetworkInterface;
use BitWasp\Buffertools\Parser;
use BitWasp\Bitcoin\Key\Deterministic\HierarchicalKey;
use BitWasp\Buffertools\TemplateFactory;

class ExtendedKeySerializer
{
    /**
     * @var EcAdapterInterface
     */
    private $ecAdapter;

    /**
     * @param EcAdapterInterface $ecAdapter
     * @throws \Exception
     */
    public function __construct(EcAdapterInterface $ecAdapter)
    {
        $this->ecAdapter = $ecAdapter;
    }

    /**
     * @return \BitWasp\Buffertools\Template
     */
    public function getTemplate()
    {
        return (new TemplateFactory())
            ->bytestring(4)
            ->uint8()
            ->uint32()
            ->uint32()
            ->bytestring(32)
            ->bytestring(33)
            ->getTemplate();
    }

    /**
     * @param NetworkInterface $network
     * @throws \Exception
     */
    private function checkNetwork(NetworkInterface $network)
    {
        try {
            $network->getHDPrivByte();
            $network->getHDPubByte();
        } catch (\Exception $e) {
            throw new \Exception('Network not configured for HD wallets');
        }
    }

    /**
     * @param NetworkInterface $network
     * @param HierarchicalKey $key
     * @return Buffer
     */
    public function serialize(NetworkInterface $network, HierarchicalKey $key)
    {
        $this->checkNetwork($network);

        list ($prefix, $data) = ($key->isPrivate())
            ? [$network->getHDPrivByte(), '00' . $key->getPrivateKey()->getHex()]
            : [$network->getHDPubByte(), $key->getPublicKey()->getHex()];

        return $this->getTemplate()->write([
            Buffer::hex($prefix, 4),
            $key->getDepth(),
            $key->getFingerprint(),
            $key->getSequence(),
            $key->getChainCode(),
            Buffer::hex($data, 33)
        ]);
    }

    /**
     * @param NetworkInterface $network
     * @param Parser $parser
     * @return HierarchicalKey
     * @throws ParserOutOfRange
     */
    public function fromParser(NetworkInterface $network, Parser $parser)
    {
        $this->checkNetwork($network);

        try {
            list ($bytes, $depth, $parentFingerprint, $sequence, $chainCode, $keyData) = $this->getTemplate()->parse($parser);
            /** @var BufferInterface $keyData */
            /** @var BufferInterface $bytes */
            $bytes = $bytes->getHex();
        } catch (ParserOutOfRange $e) {
            throw new ParserOutOfRange('Failed to extract HierarchicalKey from parser');
        }

        if ($bytes !== $network->getHDPubByte() && $bytes !== $network->getHDPrivByte()) {
            throw new \InvalidArgumentException('HD key magic bytes do not match network magic bytes');
        }

        $key = ($network->getHDPrivByte() === $bytes)
            ? PrivateKeyFactory::fromHex($keyData->slice(1)->getHex(), true, $this->ecAdapter)
            : PublicKeyFactory::fromHex($keyData->getHex(), $this->ecAdapter);

        return new HierarchicalKey($this->ecAdapter, $depth, $parentFingerprint, $sequence, $chainCode, $key);
    }

    /**
     * @param NetworkInterface $network
     * @param BufferInterface $buffer
     * @return HierarchicalKey
     */
    public function parse(NetworkInterface $network, BufferInterface $buffer)
    {
        $parser = new Parser($buffer);
        return $this->fromParser($network, $parser);
    }
}
