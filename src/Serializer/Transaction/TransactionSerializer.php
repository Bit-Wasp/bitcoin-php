<?php

namespace Afk11\Bitcoin\Serializer\Transaction;

use Afk11\Bitcoin\Buffer;
use Afk11\Bitcoin\Parser;
use Afk11\Bitcoin\Transaction\Transaction;
use Afk11\Bitcoin\Transaction\TransactionInterface;

class TransactionSerializer
{

    public function serialize(TransactionInterface $transaction)
    {
        $parser = new Parser();
        $parser->writeInt(4, $transaction->getVersion(), true);
        foreach ($transaction->getInputs()->getInputs() as $input) {
            $parser
                ->writeBytes(32, $input->getTransactionId(), true)
                ->writeInt(4, $input->getVout())
                ->writeWithLength(
                    new Buffer($input->getScript()->serialize())
                );
        }

        foreach ($transaction->getOutputs()->getOutputs() as $output) {
            $parser
                ->writeInt(8, $output->getValue(), true)
                ->writeWithLength(
                    new Buffer($$output->getScript()->serialize())
                );
        }

        $parser->writeInt(4, $transaction->getLockTime(), true);

        return $parser
            ->getBuffer()
            ->serialize('hex');
    }

    public function parse($hex)
    {
        $parser = new Parser($hex);
        $transaction = new Transaction;
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
                    $input->fromParser($parser);
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
}
