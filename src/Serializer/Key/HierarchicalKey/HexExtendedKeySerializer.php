<?php


namespace BitWasp\Bitcoin\Serializer\Key\HierarchicalKey;

use BitWasp\Buffertools\Buffer;
use BitWasp\Bitcoin\Crypto\EcAdapter\EcAdapterInterface;
use BitWasp\Buffertools\Exceptions\ParserOutOfRange;
use BitWasp\Bitcoin\Key\PrivateKeyFactory;
use BitWasp\Bitcoin\Key\PublicKeyFactory;
use BitWasp\Bitcoin\Network\NetworkInterface;
use BitWasp\Buffertools\Parser;
use BitWasp\Bitcoin\Key\Deterministic\HierarchicalKey;
use BitWasp\Buffertools\TemplateFactory;

class HexExtendedKeySerializer
{
    /**
     * @var NetworkInterface
     */
    private $network;

    /**
     * @var EcAdapterInterface
     */
    private $ecAdapter;

    /**
     * @param EcAdapterInterface $ecAdapter
     * @param NetworkInterface $network
     * @throws \Exception
     */
    public function __construct(EcAdapterInterface $ecAdapter, NetworkInterface $network)
    {
        try {
            $network->getHDPrivByte();
            $network->getHDPubByte();
        } catch (\Exception $e) {
            throw new \Exception('Network not configured for HD wallets');
        }

        $this->network = $network;
        $this->ecAdapter = $ecAdapter;
    }

    public function getTemplate()
    {
        return (new TemplateFactory())
            ->bytestring(4)
            ->uint8()
            ->uint32()
            ->uint32()
            ->uint256()
            ->bytestring(33)
            ->getTemplate();
    }

    /**
     * @param HierarchicalKey $key
     * @return \BitWasp\Buffertools\Buffer
     */
    public function serialize(HierarchicalKey $key)
    {
        list ($prefix, $data) = ($key->isPrivate())
            ? [$this->network->getHDPrivByte(), '00' . $key->getPrivateKey()->getHex()]
            : [$this->network->getHDPubByte(), $key->getPublicKey()->getHex()];

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
     * @param Parser $parser
     * @return HierarchicalKey
     * @throws ParserOutOfRange
     */
    public function fromParser(Parser & $parser)
    {
        try {
            list ($bytes, $depth, $parentFingerprint, $sequence, $chainCode, $keyData) = $this->getTemplate()->parse($parser);
            $bytes = $bytes->getHex();
        } catch (ParserOutOfRange $e) {
            throw new ParserOutOfRange('Failed to extract HierarchicalKey from parser');
        }

        if ($bytes !== $this->network->getHDPubByte() && $bytes !== $this->network->getHDPrivByte()) {
            throw new \InvalidArgumentException("HD key magic bytes do not match network magic bytes");
        }

        $key = ($this->network->getHDPrivByte() == $bytes)
            ? PrivateKeyFactory::fromHex($keyData->slice(1)->getHex(), true, $this->ecAdapter)
            : PublicKeyFactory::fromHex($keyData->getHex(), $this->ecAdapter);

        return new HierarchicalKey($this->ecAdapter, $depth, $parentFingerprint, $sequence, $chainCode, $key);
    }

    /**
     * @param Buffer $buffer
     * @return \BitWasp\Bitcoin\Key\Deterministic\HierarchicalKey
     * @throws ParserOutOfRange
     * @throws \Exception
     */
    public function parse(Buffer $buffer)
    {
        $parser = new Parser($buffer);
        $hd = $this->fromParser($parser);
        return $hd;
    }
}
