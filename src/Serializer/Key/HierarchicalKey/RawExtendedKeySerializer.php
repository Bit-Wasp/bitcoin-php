<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Serializer\Key\HierarchicalKey;

use BitWasp\Bitcoin\Crypto\EcAdapter\Adapter\EcAdapterInterface;
use BitWasp\Bitcoin\Serializer\Types;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\BufferInterface;
use BitWasp\Buffertools\Exceptions\ParserOutOfRange;
use BitWasp\Buffertools\Parser;

class RawExtendedKeySerializer
{
    /**
     * @var EcAdapterInterface
     */
    private $ecAdapter;

    /**
     * @var \BitWasp\Buffertools\Types\ByteString
     */
    private $bytestring4;

    /**
     * @var \BitWasp\Buffertools\Types\Uint8
     */
    private $uint8;

    /**
     * @var \BitWasp\Buffertools\Types\Uint32
     */
    private $uint32;

    /**
     * @var \BitWasp\Buffertools\Types\ByteString
     */
    private $bytestring32;

    /**
     * @var \BitWasp\Buffertools\Types\ByteString
     */
    private $bytestring33;

    /**
     * RawExtendedKeySerializer constructor.
     * @param EcAdapterInterface $ecAdapter
     */
    public function __construct(EcAdapterInterface $ecAdapter)
    {
        $this->ecAdapter = $ecAdapter;
        $this->bytestring4 = Types::bytestring(4);
        $this->uint8 = Types::uint8();
        $this->uint32 = Types::uint32();
        $this->bytestring32 = Types::bytestring(32);
        $this->bytestring33 = Types::bytestring(33);
    }

    /**
     * @param RawKeyParams $keyParams
     * @return BufferInterface
     * @throws \Exception
     */
    public function serialize(RawKeyParams $keyParams): BufferInterface
    {
        return new Buffer(
            pack("H*", $keyParams->getPrefix()) .
            $this->uint8->write($keyParams->getDepth()) .
            $this->uint32->write($keyParams->getParentFingerprint()) .
            $this->uint32->write($keyParams->getSequence()) .
            $this->bytestring32->write($keyParams->getChainCode()) .
            $this->bytestring33->write($keyParams->getKeyData())
        );
    }

    /**
     * @param Parser $parser
     * @return RawKeyParams
     * @throws ParserOutOfRange
     */
    public function fromParser(Parser $parser): RawKeyParams
    {
        try {
            return new RawKeyParams(
                $this->bytestring4->read($parser)->getHex(),
                (int) $this->uint8->read($parser),
                (int) $this->uint32->read($parser),
                (int) $this->uint32->read($parser),
                $this->bytestring32->read($parser),
                $this->bytestring33->read($parser)
            );
        } catch (ParserOutOfRange $e) {
            throw new ParserOutOfRange('Failed to extract HierarchicalKey from parser');
        }
    }
}
