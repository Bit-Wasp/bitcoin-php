<?php

namespace BitWasp\Bitcoin\Serializer\Block;

use BitWasp\Bitcoin\Block\Block;
use BitWasp\Bitcoin\Block\BlockInterface;
use BitWasp\Bitcoin\Math\Math;
use BitWasp\Bitcoin\Serializer\Transaction\TransactionSerializerInterface;
use BitWasp\Bitcoin\Serializer\Types;
use BitWasp\Buffertools\Buffertools;
use BitWasp\Buffertools\Exceptions\ParserOutOfRange;
use BitWasp\Buffertools\Parser;
use BitWasp\Buffertools\Template;

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
     * @var TransactionSerializerInterface
     */
    private $txSerializer;

    /**
     * @var \BitWasp\Buffertools\Template
     */
    private $txsTemplate;

    /**
     * @param Math $math
     * @param BlockHeaderSerializer $headerSerializer
     * @param TransactionSerializerInterface $txSerializer
     */
    public function __construct(Math $math, BlockHeaderSerializer $headerSerializer, TransactionSerializerInterface $txSerializer)
    {
        $this->math = $math;
        $this->headerSerializer = $headerSerializer;
        $this->txSerializer = $txSerializer;
        $this->txsTemplate = $this->getTxsTemplate();
    }

    /**
     * @return \BitWasp\Buffertools\Template
     */
    private function getTxsTemplate()
    {
        return new Template([
            Types::vector(function (Parser $parser) {
                return $this->txSerializer->fromParser($parser);
            })
        ]);
    }

    /**
     * @param Parser $parser
     * @return BlockInterface
     * @throws ParserOutOfRange
     */
    public function fromParser(Parser $parser)
    {
        try {
            $header = $this->headerSerializer->fromParser($parser);
            $nTx = Types::varint()->read($parser);
            $vTx = [];
            for ($i = 0; $i < $nTx; $i++) {
                $vTx[] = $this->txSerializer->fromParser($parser);
            }
            return new Block($this->math, $header, $vTx);
        } catch (ParserOutOfRange $e) {
            throw new ParserOutOfRange('Failed to extract full block header from parser');
        }
    }

    /**
     * @param \BitWasp\Buffertools\BufferInterface|string $string
     * @return BlockInterface
     * @throws ParserOutOfRange
     */
    public function parse($string)
    {
        return $this->fromParser(new Parser($string));
    }

    /**
     * @param BlockInterface $block
     * @return \BitWasp\Buffertools\BufferInterface
     */
    public function serialize(BlockInterface $block)
    {
        $parser = new Parser($this->headerSerializer->serialize($block->getHeader()));
        $parser->appendBuffer(Buffertools::numToVarInt(count($block->getTransactions())));
        foreach ($block->getTransactions() as $tx) {
            $parser->appendBuffer($this->txSerializer->serialize($tx));
        }

        return $parser->getBuffer();
    }
}
