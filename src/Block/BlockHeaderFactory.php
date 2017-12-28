<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Block;

use BitWasp\Bitcoin\Serializer\Block\BlockHeaderSerializer;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\BufferInterface;

class BlockHeaderFactory
{
    /**
     * @param string $string
     * @return BlockHeaderInterface
     * @throws \BitWasp\Buffertools\Exceptions\ParserOutOfRange
     * @throws \Exception
     */
    public static function fromHex(string $string): BlockHeaderInterface
    {
        return self::fromBuffer(Buffer::hex($string));
    }

    /**
     * @param BufferInterface $buffer
     * @return BlockHeaderInterface
     * @throws \BitWasp\Buffertools\Exceptions\ParserOutOfRange
     */
    public static function fromBuffer(BufferInterface $buffer): BlockHeaderInterface
    {
        return (new BlockHeaderSerializer())->parse($buffer);
    }
}
