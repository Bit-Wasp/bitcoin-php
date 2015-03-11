<?php

namespace Afk11\Bitcoin\Transaction;

use Afk11\Bitcoin\Parser;
use Afk11\Bitcoin\Serializer\Transaction\TransactionSerializer;

class TransactionFactory
{
    public static function create()
    {
        $transaction = new Transaction;
        return $transaction;
    }

    public static function fromHex($string)
    {
        $serializer = new TransactionSerializer;
        $hex = $serializer->parse($string);
        return $hex;
    }

    public static function fromParser(Parser &$parser)
    {
        $transaction = new Transaction;
        $transaction->setVersion($parser->readBytes(4, true)->serialize('int'));

        $inputC = $parser->getVarInt()->serialize('int');

        for ($i = 0; $i < $inputC; $i++) {
            $input = new TransactionInput();
            $transaction->getInputs()->addInput(
                $input->fromParser($parser)
            );
        }

        $outputC = $parser->getVarInt()->serialize('int');
        for ($i = 0; $i < $outputC; $i++) {
            $output = new TransactionOutput();
            $transaction->getOutputs()->addOutput(
                $output->fromParser($parser)
            );
        }

        $transaction->setLockTime($parser->readBytes(4, true)->serialize('int'));
        return $transaction;
    }
}
