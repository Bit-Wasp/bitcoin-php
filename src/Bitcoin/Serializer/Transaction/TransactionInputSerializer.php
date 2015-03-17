<?php

namespace Afk11\Bitcoin\Serializer\Transaction;

use Afk11\Bitcoin\Parser;
use Afk11\Bitcoin\Buffer;
use Afk11\Bitcoin\Script\Script;
use Afk11\Bitcoin\Transaction\TransactionInput;
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
        return $parser
            ->writeBytes(32, $input->getTransactionId(), true)
            ->writeInt(4, $input->getVout(), true)
            ->writeWithLength($input->getScript()->getBuffer())
            ->writeInt(4, $input->getSequence(), true)
            ->getBuffer();
    }

    /**
     * @param Parser $parser
     * @return TransactionInput
     * @throws \Afk11\Bitcoin\Exceptions\ParserOutOfRange
     */
    public function fromParser(Parser & $parser)
    {
        return new TransactionInput(
            $parser->readBytes(32, true)->serialize('hex'),
            $parser->readBytes(4, true)->serialize('int'),
            new Script($parser->getVarString()),
            $parser->readBytes(4, true)->serialize('int')
        );
    }

    /**
     * @param $string
     * @return TransactionInput
     * @throws \Afk11\Bitcoin\Exceptions\ParserOutOfRange
     */
    public function parse($string)
    {
        $parser = new Parser($string);
        $input = $this->fromParser($parser);
        return $input;
    }
}
