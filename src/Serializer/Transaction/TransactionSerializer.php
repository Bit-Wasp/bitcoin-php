<?php

namespace Afk11\Bitcoin\Serializer\Transaction;

use Afk11\Bitcoin\Buffer;
use Afk11\Bitcoin\Parser;
use Afk11\Bitcoin\Transaction\Transaction;
use Afk11\Bitcoin\Transaction\TransactionFactory;
use Afk11\Bitcoin\Transaction\TransactionInterface;

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
        $inputs = $this->inputsSerializer->serialize($transaction->getInputs());
        $outputs = $this->outputsSerializer->serialize($transaction->getOutputs());

        $parser = new Parser();
        $parser->writeInt(4, $transaction->getVersion(), true);
        $parser->writeArray($inputs);
        $parser->writeArray($outputs);
        $parser->writeInt(4, $transaction->getLockTime(), true);

        return $parser
            ->getBuffer();
    }

    /**
     * @param Parser $parser
     * @return Transaction
     * @throws \Afk11\Bitcoin\Exceptions\ParserOutOfRange
     * @throws \Exception
     */
    public function fromParser(Parser &$parser)
    {
        $transaction = TransactionFactory::create();
        $transaction->setVersion($parser->readBytes(4, true)->serialize('int'));
        $transaction->getInputs()->addInputs(
            $parser->getArray(
                function () use (&$parser) {
                    $input = new \Afk11\Bitcoin\Transaction\TransactionInput();
                    $input
                        ->setTransactionId($parser->readBytes(32, true)->serialize('hex'))
                        ->setVout($parser->readBytes(4)->serialize('int'))
                        ->setScriptBuf($parser->getVarString())
                        ->setSequence($parser->readBytes(4)->serialize('int'));
                    return $input;
                }
            )
        );

        $transaction->getOutputs()->addOutputs(
            $parser->getArray(
                function () use (&$parser) {
                    $output = new \Afk11\Bitcoin\Transaction\TransactionOutput();
                    $output
                        ->setValue($parser->readBytes(8, true)->serialize('int'))
                        ->setScriptBuf($parser->getVarString());
                    return $output;
                }
            )
        );

        $transaction->setLockTime($parser->readBytes(4, true)->serialize('int'));
        return $transaction;
    }

    /**
     * @param $hex
     * @return Transaction
     */
    public function parse($hex)
    {
        $parser = new Parser($hex);
        $transaction = $this->fromParser($parser);
        return $transaction;
    }
}
