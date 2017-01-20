<?php


namespace BitWasp\Bitcoin\Serializer\Key\HierarchicalKey;

use BitWasp\Bitcoin\Crypto\EcAdapter\Adapter\EcAdapterInterface;
use BitWasp\Bitcoin\Key\Deterministic\HierarchicalKey;
use BitWasp\Bitcoin\Key\PrivateKeyFactory;
use BitWasp\Bitcoin\Key\PublicKeyFactory;
use BitWasp\Bitcoin\Network\NetworkInterface;
use BitWasp\Bitcoin\Serializer\Types;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\BufferInterface;
use BitWasp\Buffertools\Exceptions\ParserOutOfRange;
use BitWasp\Buffertools\Parser;
use BitWasp\Buffertools\Template;

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
        $uint32 = Types::uint32();
        $this->template = new Template([
            Types::bytestring(4),
            Types::uint8(),
            $uint32,
            $uint32,
            Types::bytestring(32),
            Types::bytestring(33)
        ]);
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

        list ($prefix, $data) = $key->isPrivate()
            ? [$network->getHDPrivByte(), new Buffer("\x00". $key->getPrivateKey()->getBinary(), 33)]
            : [$network->getHDPubByte(), $key->getPublicKey()->getBuffer()];

        return $this->template->write([
            Buffer::hex($prefix, 4),
            $key->getDepth(),
            $key->getFingerprint(),
            $key->getSequence(),
            $key->getChainCode(),
            $data
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
            list ($bytes, $depth, $parentFingerprint, $sequence, $chainCode, $keyData) = $this->template->parse($parser);

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
            ? PrivateKeyFactory::fromHex($keyData->slice(1), true, $this->ecAdapter)
            : PublicKeyFactory::fromHex($keyData, $this->ecAdapter);

        return new HierarchicalKey($this->ecAdapter, $depth, $parentFingerprint, $sequence, $chainCode, $key);
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
