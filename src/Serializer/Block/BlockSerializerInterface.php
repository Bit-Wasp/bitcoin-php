<?php

namespace BitWasp\Bitcoin\Serializer\Block;

use BitWasp\Bitcoin\Block\BlockInterface;
use BitWasp\Buffertools\Exceptions\ParserOutOfRange;
use BitWasp\Buffertools\Parser;

interface BlockSerializerInterface
{
    /**
     * @param Parser $parser
     * @return BlockInterface
     * @throws ParserOutOfRange
     */
    public function fromParser(Parser $parser);

    /**
     * @param \BitWasp\Buffertools\BufferInterface|string $string
     * @return BlockInterface
     * @throws ParserOutOfRange
     */
    public function parse($string);

    /**
     * @param BlockInterface $block
     * @return \BitWasp\Buffertools\BufferInterface
     */
    public function serialize(BlockInterface $block);
}
