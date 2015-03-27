<?php

namespace BitWasp\Bitcoin\Serializer\Block;

use BitWasp\Bitcoin\Block\Block;
use BitWasp\Bitcoin\Exceptions\ParserOutOfRange;
use BitWasp\Bitcoin\Math\Math;
use BitWasp\Bitcoin\Parser;
use BitWasp\Bitcoin\Block\BlockInterface;
use BitWasp\Bitcoin\Serializer\Transaction\TransactionCollectionSerializer;

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
     * @var TransactionCollectionSerializer
     */
    protected $txColSerializer;

    /**
     * @param Math $math
     * @param HexBlockHeaderSerializer $headerSerializer
     * @param TransactionCollectionSerializer $txColSerializer
     */
    public function __construct(Math $math, HexBlockHeaderSerializer $headerSerializer, TransactionCollectionSerializer $txColSerializer)
    {
        $this->math = $math;
        $this->headerSerializer = $headerSerializer;
        $this->txColSerializer = $txColSerializer;
    }

    /**
     * @param Parser $parser
     * @return Block
     * @throws ParserOutOfRange
     */
    public function fromParser(Parser & $parser)
    {
        try {
            $block = new Block($this->math);
            $block->setHeader($this->headerSerializer->fromParser($parser));
            $block->setTransactions($this->txColSerializer->fromParser($parser));
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
     * @return \BitWasp\Bitcoin\Buffer
     */
    public function serialize(BlockInterface $block)
    {
        $header = $block->getHeader()->getBuffer();
        $parser = new Parser($header);
        $serializedTxs = $this->txColSerializer->serialize($block->getTransactions());
        $parser->writeArray($serializedTxs);
        return $parser->getBuffer();
    }
}
