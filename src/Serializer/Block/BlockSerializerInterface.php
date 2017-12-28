<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Serializer\Block;

use BitWasp\Bitcoin\Block\BlockInterface;
use BitWasp\Buffertools\BufferInterface;
use BitWasp\Buffertools\Exceptions\ParserOutOfRange;
use BitWasp\Buffertools\Parser;

interface BlockSerializerInterface
{
    /**
     * @param Parser $parser
     * @return BlockInterface
     * @throws ParserOutOfRange
     */
    public function fromParser(Parser $parser): BlockInterface;

    /**
     * @param BufferInterface $buffer
     * @return BlockInterface
     * @throws ParserOutOfRange
     */
    public function parse(BufferInterface $buffer): BlockInterface;

    /**
     * @param BlockInterface $block
     * @return BufferInterface
     */
    public function serialize(BlockInterface $block): BufferInterface;
}
