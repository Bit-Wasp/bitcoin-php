<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Serializer\Block;

use BitWasp\Bitcoin\Block\Block;
use BitWasp\Bitcoin\Block\BlockInterface;
use BitWasp\Bitcoin\Math\Math;
use BitWasp\Bitcoin\Serializer\Transaction\TransactionSerializerInterface;
use BitWasp\Bitcoin\Serializer\Types;
use BitWasp\Buffertools\BufferInterface;
use BitWasp\Buffertools\Exceptions\ParserOutOfRange;
use BitWasp\Buffertools\Parser;

class BlockSerializer implements BlockSerializerInterface
{
    /**
     * @var Math
     */
    private $math;

    /**
     * @var BlockHeaderSerializer
     */
    private $headerSerializer;

    /**
     * @var \BitWasp\Buffertools\Types\VarInt
     */
    private $varint;

    /**
     * @var TransactionSerializerInterface
     */
    private $txSerializer;

    /**
     * @param Math $math
     * @param BlockHeaderSerializer $headerSerializer
     * @param TransactionSerializerInterface $txSerializer
     */
    public function __construct(Math $math, BlockHeaderSerializer $headerSerializer, TransactionSerializerInterface $txSerializer)
    {
        $this->math = $math;
        $this->headerSerializer = $headerSerializer;
        $this->varint = Types::varint();
        $this->txSerializer = $txSerializer;
    }

    /**
     * @param Parser $parser
     * @return BlockInterface
     * @throws ParserOutOfRange
     */
    public function fromParser(Parser $parser): BlockInterface
    {
        try {
            $header = $this->headerSerializer->fromParser($parser);
            $nTx = $this->varint->read($parser);
            $vTx = [];
            for ($i = 0; $i < $nTx; $i++) {
                $vTx[] = $this->txSerializer->fromParser($parser);
            }
            return new Block($this->math, $header, ...$vTx);
        } catch (ParserOutOfRange $e) {
            throw new ParserOutOfRange('Failed to extract full block header from parser');
        }
    }

    /**
     * @param BufferInterface $buffer
     * @return BlockInterface
     * @throws ParserOutOfRange
     */
    public function parse(BufferInterface $buffer): BlockInterface
    {
        return $this->fromParser(new Parser($buffer));
    }

    /**
     * @param BlockInterface $block
     * @return BufferInterface
     */
    public function serialize(BlockInterface $block): BufferInterface
    {
        $parser = new Parser($this->headerSerializer->serialize($block->getHeader()));
        $parser->appendBinary($this->varint->write(count($block->getTransactions())));
        foreach ($block->getTransactions() as $tx) {
            $parser->appendBuffer($this->txSerializer->serialize($tx));
        }

        return $parser->getBuffer();
    }
}
