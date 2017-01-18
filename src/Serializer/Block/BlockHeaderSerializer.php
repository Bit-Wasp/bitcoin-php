<?php

namespace BitWasp\Bitcoin\Serializer\Block;

use BitWasp\Bitcoin\Serializer\Types;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\Exceptions\ParserOutOfRange;
use BitWasp\Buffertools\Parser;
use BitWasp\Bitcoin\Block\BlockHeader;
use BitWasp\Bitcoin\Block\BlockHeaderInterface;

class BlockHeaderSerializer
{
    public function __construct()
    {
        $this->hash = Types::bytestringle(32);
        $this->uint32le = Types::uint32le();
        $this->int32le = Types::int32le();
    }

    /**
     * @param \BitWasp\Buffertools\BufferInterface|string $string
     * @return BlockHeader
     * @throws ParserOutOfRange
     */
    public function parse($string)
    {
        return $this->fromParser(new Parser($string));
    }

    /**
     * @param Parser $parser
     * @return BlockHeader
     * @throws ParserOutOfRange
     */
    public function fromParser(Parser $parser)
    {
        try {
            return new BlockHeader(
                $this->int32le->read($parser),
                $this->hash->read($parser),
                $this->hash->read($parser),
                (int) $this->uint32le->read($parser),
                (int) $this->uint32le->read($parser),
                (int) $this->uint32le->read($parser)
            );
        } catch (ParserOutOfRange $e) {
            throw new ParserOutOfRange('Failed to extract full block header from parser');
        }
    }

    /**
     * @param BlockHeaderInterface $header
     * @return \BitWasp\Buffertools\BufferInterface
     */
    public function serialize(BlockHeaderInterface $header)
    {
        return new Buffer(
            $this->int32le->write($header->getVersion()) .
            $this->hash->write($header->getPrevBlock()) .
            $this->hash->write($header->getMerkleRoot()) .
            $this->uint32le->write($header->getTimestamp()) .
            $this->uint32le->write($header->getBits()) .
            $this->uint32le->write($header->getNonce())
        );
    }
}
