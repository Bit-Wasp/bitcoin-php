<?php

namespace BitWasp\Bitcoin\Serializer\Transaction;

use BitWasp\Buffertools\Buffertools;
use BitWasp\Buffertools\Parser;
use BitWasp\Buffertools\Buffer;
use BitWasp\Bitcoin\Script\Script;
use BitWasp\Bitcoin\Transaction\TransactionInput;
use BitWasp\Bitcoin\Transaction\TransactionInputInterface;
use BitWasp\Buffertools\TemplateFactory;

class TransactionInputSerializer
{
    /**
     * @var OutPointSerializer
     */
    private $outpointSerializer;

    /**
     * TransactionInputSerializer constructor.
     * @param OutPointSerializer $outPointSerializer
     */
    public function __construct(OutPointSerializer $outPointSerializer)
    {
        $this->outpointSerializer = $outPointSerializer;
    }

    /**
     * @return \BitWasp\Buffertools\Template
     */
    private function getInputTemplate()
    {
        return (new TemplateFactory())
            ->varstring()
            ->uint32le()
            ->getTemplate();
    }

    /**
     * @param TransactionInputInterface $input
     * @return Buffer
     */
    public function serialize(TransactionInputInterface $input)
    {
        return Buffertools::concat(
            $this->outpointSerializer->serialize($input->getOutPoint()),
            $this->getInputTemplate()->write([
                $input->getScript()->getBuffer(),
                $input->getSequence()
            ])
        );
    }

    /**
     * @param Parser $parser
     * @return TransactionInput
     * @throws \BitWasp\Buffertools\Exceptions\ParserOutOfRange
     */
    public function fromParser(Parser $parser)
    {
        $outpoint = $this->outpointSerializer->fromParser($parser);

        /**
         * @var Buffer $scriptBuf
         * @var int|string $sequence
         */
        list ($scriptBuf, $sequence) = $this->getInputTemplate()->parse($parser);

        return new TransactionInput(
            $outpoint,
            new Script($scriptBuf),
            $sequence
        );
    }

    /**
     * @param $string
     * @return TransactionInput
     * @throws \BitWasp\Buffertools\Exceptions\ParserOutOfRange
     */
    public function parse($string)
    {
        $parser = new Parser($string);
        return $this->fromParser($parser);
    }
}
