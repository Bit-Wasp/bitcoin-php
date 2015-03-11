<?php

namespace Afk11\Bitcoin\Serializer\Transaction;

use Afk11\Bitcoin\Parser;
use Afk11\Bitcoin\Buffer;
use Afk11\Bitcoin\Transaction\TransactionOutputInterface;

class TransactionOutputSerializer
{
    /**
     * @param TransactionOutputInterface $output
     * @return Buffer
     */
    public function serialize(TransactionOutputInterface $output)
    {
        $parser = new Parser();
        $parser
            ->writeInt(8, $output->getValue(), true)
            ->writeWithLength(
                new Buffer($output->getScript()->serialize())
            );

        return $parser->getBuffer();
    }

    /**
     * @param $string
     * @return \Afk11\Bitcoin\Transaction\TransactionOutput
     * @throws \Afk11\Bitcoin\Exceptions\ParserOutOfRange
     */
    public function parse($string)
    {
        $output = new \Afk11\Bitcoin\Transaction\TransactionOutput();
        $parser = new Parser($string);
        $output
            ->setValue($parser->readBytes(8, true)->serialize('int'))
            ->setScriptBuf($parser->getVarString());
        return $output;

    }
}
