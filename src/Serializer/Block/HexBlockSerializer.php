<?php

namespace BitWasp\Bitcoin\Serializer\Block;

use BitWasp\Bitcoin\Block\Block;
use BitWasp\Buffertools\Buffertools;
use BitWasp\Buffertools\Exceptions\ParserOutOfRange;
use BitWasp\Bitcoin\Math\Math;
use BitWasp\Buffertools\Parser;
use BitWasp\Bitcoin\Block\BlockInterface;
use BitWasp\Bitcoin\Serializer\Transaction\TransactionSerializer;
use BitWasp\Buffertools\TemplateFactory;

class HexBlockSerializer
{
    /**
     * @var Math
     */
    private $math;

    /**
     * @var HexBlockHeaderSerializer
     */
    private $headerSerializer;

    /**
     * @var TransactionSerializer
     */
    private $txSerializer;

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
     * @return \BitWasp\Buffertools\Template
     */
    private function getTxsTemplate()
    {
        return (new TemplateFactory())
            ->vector(function (Parser &$parser) {
                return $this->txSerializer->fromParser($parser);
            })
            ->getTemplate();
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

            $block->getTransactions()->addTransactions($this->getTxsTemplate()->parse($parser)[0]);
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
        return Buffertools::concat(
            $block->getHeader()->getBuffer(),
            $this->getTxsTemplate()->write([
                $block->getTransactions()->getTransactions()
            ])
        );
    }
}
