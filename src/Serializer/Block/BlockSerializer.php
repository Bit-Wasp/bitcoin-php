<?php

namespace BitWasp\Bitcoin\Serializer\Block;

use BitWasp\Bitcoin\Block\Block;
use BitWasp\Bitcoin\Collection\Transaction\TransactionCollection;
use BitWasp\Buffertools\Buffertools;
use BitWasp\Buffertools\Exceptions\ParserOutOfRange;
use BitWasp\Bitcoin\Math\Math;
use BitWasp\Buffertools\Parser;
use BitWasp\Bitcoin\Block\BlockInterface;
use BitWasp\Bitcoin\Serializer\Transaction\MTransactionSerializer;
use BitWasp\Buffertools\TemplateFactory;

class BlockSerializer
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
     * @var TransactionSerializer
     */
    private $txSerializer;

    /**
     * @param Math $math
     * @param BlockHeaderSerializer $headerSerializer
     * @param TransactionSerializer $txSerializer
     */
    public function __construct(Math $math, BlockHeaderSerializer $headerSerializer, MTransactionSerializer $txSerializer)
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
     * @return BlockInterface
     * @throws ParserOutOfRange
     */
    public function fromParser(Parser $parser)
    {
        try {
            return new Block(
                $this->math,
                $this->headerSerializer->fromParser($parser),
                new TransactionCollection($this->getTxsTemplate()->parse($parser)[0])
            );
        } catch (ParserOutOfRange $e) {
            throw new ParserOutOfRange('Failed to extract full block header from parser');
        }
    }

    /**
     * @param \BitWasp\Buffertools\Buffer|string $string
     * @return BlockInterface
     * @throws ParserOutOfRange
     */
    public function parse($string)
    {
        return $this->fromParser(new Parser($string));
    }

    /**
     * @param BlockInterface $block
     * @return \BitWasp\Buffertools\Buffer
     */
    public function serialize(BlockInterface $block)
    {
        return Buffertools::concat(
            $this->headerSerializer->serialize($block->getHeader()),
            $this->getTxsTemplate()->write([$block->getTransactions()->all()])
        );
    }
}
