<?php

namespace BitWasp\Bitcoin\Serializer\Block;

use BitWasp\Bitcoin\Block\Block;
use BitWasp\Buffertools\Exceptions\ParserOutOfRange;
use BitWasp\Bitcoin\Math\Math;
use BitWasp\Buffertools\Parser;
use BitWasp\Bitcoin\Block\BlockInterface;
use BitWasp\Bitcoin\Serializer\Transaction\TransactionSerializer;

class HexBlockSerializer
{
    /**
     * @var Math
     */
    protected $math;

    /**
     * @var HexBlockHeaderSerializer
     */
    protected $headerSerializer;

    /**
     * @var TransactionSerializer
     */
    protected $txSerializer;

    /**
     * @param Math $math
     * @param HexBlockHeaderSerializer $headerSerializer
     * @param TransactionSerializer $txSerializer
     */
    public function __construct(Math $math, HexBlockHeaderSerializer $headerSerializer, TransactionSerializer $txSerializer)
    {
        $this->math = $math;
        $this->headerSerializer = $headerSerializer;
        $this->txSerializer = $txSerializer;
    }

    /**
     * @param Parser $parser
     * @return Block
     * @throws ParserOutOfRange
     */
    public function fromParser(Parser & $parser)
    {
        try {
            $block = new Block(
                $this->math,
                $this->headerSerializer->fromParser($parser)
            );

            $block->getTransactions()->addTransactions(
                $parser->getArray(
                    function () use (&$parser) {
                        $transaction = $this->txSerializer->fromParser($parser);
                        return $transaction;
                    }
                )
            );
        } catch (ParserOutOfRange $e) {
            throw new ParserOutOfRange('Failed to extract full block header from parser');
        }

        return $block;
    }

    /**
     * @param $string
     * @return Block
     * @throws ParserOutOfRange
     */
    public function parse($string)
    {
        $parser = new Parser($string);
        $block = $this->fromParser($parser);
        return $block;
    }

    /**
     * @param BlockInterface $block
     * @return \BitWasp\Buffertools\Buffer
     */
    public function serialize(BlockInterface $block)
    {
        $header = $block->getHeader()->getBuffer();
        $parser = new Parser($header);
        $parser->writeArray($block->getTransactions()->getTransactions());
        return $parser->getBuffer();
    }
}
