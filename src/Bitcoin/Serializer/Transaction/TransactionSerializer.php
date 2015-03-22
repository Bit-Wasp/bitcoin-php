<?php

namespace BitWasp\Bitcoin\Serializer\Transaction;

use BitWasp\Bitcoin\Parser;
use BitWasp\Bitcoin\Transaction\Transaction;
use BitWasp\Bitcoin\Transaction\TransactionFactory;
use BitWasp\Bitcoin\Transaction\TransactionInterface;

class TransactionSerializer
{
    /**
     * @var TransactionInputCollectionSerializer
     */
    public $inputsSerializer;

    /**
     * @var TransactionOutputCollectionSerializer
     */
    public $outputsSerializer;

    /**
     *
     */
    public function __construct()
    {
        $this->inputsSerializer = new TransactionInputCollectionSerializer(new TransactionInputSerializer);
        $this->outputsSerializer = new TransactionOutputCollectionSerializer(new TransactionOutputSerializer);
    }

    /**
     * @param TransactionInterface $transaction
     * @return string
     */
    public function serialize(TransactionInterface $transaction)
    {
        $parser = new Parser();
        return $parser
            ->writeInt(4, $transaction->getVersion(), true)
            ->writeArray($transaction->getInputs()->getBuffer())
            ->writeArray($transaction->getOutputs()->getBuffer())
            ->writeInt(4, $transaction->getLockTime(), true)
            ->getBuffer();
    }

    /**
     * @param Parser $parser
     * @return Transaction
     * @throws \BitWasp\Bitcoin\Exceptions\ParserOutOfRange
     * @throws \Exception
     */
    public function fromParser(Parser & $parser)
    {
        return TransactionFactory::create()
            ->setVersion($parser->readBytes(4, true)->serialize('int'))
            ->setInputs($this->inputsSerializer->fromParser($parser))
            ->setOutputs($this->outputsSerializer->fromParser($parser))
            ->setLockTime($parser->readBytes(4, true)->serialize('int'));
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
