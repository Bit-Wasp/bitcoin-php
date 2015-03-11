<?php

namespace Afk11\Bitcoin\Serializer\Transaction;

use Afk11\Bitcoin\Parser;
use Afk11\Bitcoin\Buffer;
use Afk11\Bitcoin\Transaction\TransactionInputInterface;

class TransactionInputSerializer
{
    /**
     * @param TransactionInputInterface $input
     * @return Buffer
     */
    public function serialize(TransactionInputInterface $input)
    {
        $parser = new Parser();
        $parser
            ->writeBytes(32, $input->getTransactionId(), true)
            ->writeInt(4, $input->getVout())
            ->writeWithLength(
                new Buffer($input->getScript()->serialize())
            )
            ->writeInt(4, $input->getSequence());

        return $parser->getBuffer();
    }

    /**
     * @param $string
     * @return \Afk11\Bitcoin\Transaction\TransactionInput
     * @throws \Afk11\Bitcoin\Exceptions\ParserOutOfRange
     */
    public function parse($string)
    {
        $parser = new Parser($string);

        $input = new \Afk11\Bitcoin\Transaction\TransactionInput();
        $input
            ->setTransactionId($parser->readBytes(32, true)->serialize('hex'))
            ->setVout($parser->readBytes(4)->serialize('int'))
            ->setScriptBuf($parser->getVarString())
            ->setSequence($parser->readBytes(4)->serialize('int'));

        return $input;
    }
}
