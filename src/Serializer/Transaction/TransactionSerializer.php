<?php

namespace BitWasp\Bitcoin\Serializer\Transaction;

use BitWasp\Bitcoin\Transaction\Factory\TxBuilder;
use BitWasp\Buffertools\Parser;
use BitWasp\Bitcoin\Transaction\Transaction;
use BitWasp\Bitcoin\Transaction\TransactionInterface;
use BitWasp\Buffertools\TemplateFactory;

class TransactionSerializer
{
    /**
     * @var TransactionInputSerializer
     */
    public $inputSerializer;

    /**
     * @var TransactionOutputSerializer
     */
    public $outputSerializer;

    /**
     *
     */
    public function __construct()
    {
        $this->inputSerializer = new TransactionInputSerializer;
        $this->outputSerializer = new TransactionOutputSerializer;
    }

    /**
     * @return \BitWasp\Buffertools\Template
     */
    private function getTemplate()
    {
        return (new TemplateFactory())
            ->uint32le()
            ->vector(function (Parser & $parser) {
                return $this->inputSerializer->fromParser($parser);
            })
            ->vector(function (Parser &$parser) {
                return $this->outputSerializer->fromParser($parser);
            })
            ->uint32le()
            ->getTemplate();
    }

    /**
     * @param TransactionInterface $transaction
     * @return string
     */
    public function serialize(TransactionInterface $transaction)
    {
        return $this->getTemplate()->write([
            $transaction->getVersion(),
            $transaction->getInputs()->all(),
            $transaction->getOutputs()->all(),
            $transaction->getLockTime()
        ]);
    }

    /**
     * @param Parser $parser
     * @return Transaction
     * @throws \BitWasp\Buffertools\Exceptions\ParserOutOfRange
     * @throws \Exception
     */
    public function fromParser(Parser & $parser)
    {
        list ($version, $inputArray, $outputArray, $locktime) = $this->getTemplate()->parse($parser);

        return (new TxBuilder())
            ->version($version)
            ->inputs($inputArray)
            ->outputs($outputArray)
            ->locktime($locktime)
            ->get();
    }

    /**
     * @param $hex
     * @return Transaction
     */
    public function parse($hex)
    {
        $parser = new Parser($hex);
        return $this->fromParser($parser);
    }
}
