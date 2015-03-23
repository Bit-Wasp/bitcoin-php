<?php


namespace BitWasp\Bitcoin\Serializer\Key\HierarchicalKey;

use BitWasp\Bitcoin\Exceptions\ParserOutOfRange;
use BitWasp\Bitcoin\Key\PrivateKeyFactory;
use BitWasp\Bitcoin\Key\PublicKeyFactory;
use BitWasp\Bitcoin\Math\Math;
use BitWasp\Bitcoin\Network\NetworkInterface;
use BitWasp\Bitcoin\Parser;
use BitWasp\Bitcoin\Key\HierarchicalKey;
use Mdanter\Ecc\GeneratorPoint;

class HexExtendedKeySerializer
{
    /**
     * @var NetworkInterface
     */
    private $network;

    /**
     * @var Math
     */
    private $math;

    /**
     * @var GeneratorPoint
     */
    private $generator;

    /**
     * @param Math             $math
     * @param GeneratorPoint   $generator
     * @param NetworkInterface $network
     * @throws \Exception
     */
    public function __construct(Math $math, GeneratorPoint $generator, NetworkInterface $network)
    {
        try {
            $network->getHDPrivByte();
            $network->getHDPubByte();
        } catch (\Exception $e) {
            throw new \Exception('Network not configured for HD wallets');
        }

        $this->math = $math;
        $this->generator = $generator;
        $this->network = $network;
    }

    /**
     * @return Math
     */
    public function getMath()
    {
        return $this->math;
    }

    /**
     * @return GeneratorPoint
     */
    public function getGenerator()
    {
        return $this->generator;
    }
    /**
     * @return NetworkInterface
     */
    public function getNetwork()
    {
        return $this->network;
    }

    /**
     * @param HierarchicalKey $key
     * @return \BitWasp\Bitcoin\Buffer
     */
    public function serialize(HierarchicalKey $key)
    {
        list ($prefix, $data) = ($key->isPrivate())
            ? array($this->network->getHDPrivByte(), '00' . $key->getPrivateKey()->getBuffer())
            : array($this->network->getHDPubByte(), $key->getPublicKey()->getBuffer());

        $bytes = new Parser();
        $bytes
            ->writeBytes(4, $prefix)
            ->writeInt(1, $key->getDepth())
            ->writeInt(4, $key->getFingerprint())
            ->writeInt(4, $key->getSequence())
            ->writeInt(32, $key->getChainCode())
            ->writeBytes(33, $data);

        $hex = $bytes
            ->getBuffer();

        return $hex;
    }

    /**
     * @param Parser $parser
     * @return HierarchicalKey
     * @throws ParserOutOfRange
     */
    public function fromParser(Parser & $parser)
    {
        try {
            list ($bytes, $depth, $parentFingerprint, $sequence, $chainCode, $keyData) =
                array(
                    $parser->readBytes(4)->serialize('hex'),
                    $parser->readBytes(1)->serialize('int'),
                    $parser->readBytes(4)->serialize('int'),
                    $parser->readBytes(4)->serialize('int'),
                    $parser->readBytes(32)->serialize('int'),
                    $parser->readBytes(33)
                );
        } catch (ParserOutOfRange $e) {
            throw new ParserOutOfRange('Failed to extract extended key from parser');
        }

        if ($bytes !== $this->network->getHDPubByte() && $bytes !== $this->network->getHDPrivByte()) {
            throw new \InvalidArgumentException("HD key magic bytes do not match network magic bytes");

        }

        $key = ($this->network->getHDPrivByte() == $bytes)
            ? PrivateKeyFactory::fromHex(substr($keyData, 2))->setCompressed(true)
            : PublicKeyFactory::fromHex($keyData);

        $hd = new HierarchicalKey($this->math, $this->generator, $depth, $parentFingerprint, $sequence, $chainCode, $key);

        return $hd;
    }

    /**
     * @param string $hex
     * @return HierarchicalKey
     * @throws ParserOutOfRange
     * @throws \Exception
     */
    public function parse($hex)
    {
        if (strlen($hex) !== 156) {
            throw new \Exception('Invalid extended key');
        }

        $parser = new Parser($hex);
        $hd = $this->fromParser($parser);
        return $hd;
    }
}
