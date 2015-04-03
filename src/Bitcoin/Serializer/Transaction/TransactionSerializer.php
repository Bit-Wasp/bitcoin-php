<?php

namespace BitWasp\Bitcoin\Serializer\Transaction;

use BitWasp\Buffertools\Parser;
use BitWasp\Bitcoin\Transaction\Transaction;
use BitWasp\Bitcoin\Transaction\TransactionFactory;
use BitWasp\Bitcoin\Transaction\TransactionInterface;

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
     * @param TransactionInterface $transaction
     * @return string
     */
    public function serialize(TransactionInterface $transaction)
    {
        $parser = new Parser();
        return $parser
            ->writeInt(4, $transaction->getVersion(), true)
            ->writeArray($transaction->getInputs()->getInputs())
            ->writeArray($transaction->getOutputs()->getOutputs())
            ->writeInt(4, $transaction->getLockTime(), true)
            ->getBuffer();
    }

    /**
     * @param Parser $parser
     * @return Transaction
     * @throws \BitWasp\Buffertools\Exceptions\ParserOutOfRange
     * @throws \Exception
     */
    public function fromParser(Parser & $parser)
    {
        $tx = TransactionFactory::create()
            ->setVersion($parser->readBytes(4, true)->getInt());

        $tx->getInputs()->addInputs($parser->getArray(
            function () use (&$parser) {
                return $this->inputSerializer->fromParser($parser);
            }
        ));

        $tx->getOutputs()->addOutputs($parser->getArray(
            function () use (&$parser) {
                return $this->outputSerializer->fromParser($parser);
            }
        ));

        $tx->setLockTime($parser->readBytes(4, true)->getInt());
        return $tx;
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
