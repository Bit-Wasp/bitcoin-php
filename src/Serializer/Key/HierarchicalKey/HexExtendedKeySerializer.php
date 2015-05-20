<?php


namespace BitWasp\Bitcoin\Serializer\Key\HierarchicalKey;

use BitWasp\Buffertools\Buffer;
use BitWasp\Bitcoin\Crypto\EcAdapter\EcAdapterInterface;
use BitWasp\Buffertools\Exceptions\ParserOutOfRange;
use BitWasp\Bitcoin\Key\PrivateKeyFactory;
use BitWasp\Bitcoin\Key\PublicKeyFactory;
use BitWasp\Bitcoin\Network\NetworkInterface;
use BitWasp\Buffertools\Parser;
use BitWasp\Bitcoin\Key\HierarchicalKey;

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

    /**
     * @param HierarchicalKey $key
     * @return \BitWasp\Buffertools\Buffer
     */
    public function serialize(HierarchicalKey $key)
    {
        list ($prefix, $data) = ($key->isPrivate())
            ? [$this->network->getHDPrivByte(), '00' . $key->getPrivateKey()->getHex()]
            : [$this->network->getHDPubByte(), $key->getPublicKey()->getHex()];

        $bytes = new Parser();

        return $bytes
            ->writeBytes(4, $prefix)
            ->writeInt(1, $key->getDepth())
            ->writeInt(4, $key->getFingerprint())
            ->writeInt(4, $key->getSequence())
            ->writeInt(32, $key->getChainCode())
            ->writeBytes(33, $data)
            ->getBuffer();
    }

    /**
     * @param Parser $parser
     * @return HierarchicalKey
     * @throws ParserOutOfRange
     */
    public function fromParser(Parser & $parser)
    {
        try {
            list ($bytes, $depth, $parentFingerprint, $sequence, $chainCode) = [
                $parser->readBytes(4)->getHex(),
                $parser->readBytes(1)->getInt(),
                $parser->readBytes(4)->getInt(),
                $parser->readBytes(4)->getInt(),
                $parser->readBytes(32)->getInt()
            ];

            /** @var Buffer $keyData */
            $keyData = $parser->readBytes(33);

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
     * @return HierarchicalKey
     * @throws ParserOutOfRange
     * @throws \Exception
     */
    public function parse(Buffer $buffer)
    {
        if ($buffer->getSize() !== 78) {
            throw new \Exception('Invalid extended key');
        }

        $parser = new Parser($buffer);
        $hd = $this->fromParser($parser);
        return $hd;
    }
}
