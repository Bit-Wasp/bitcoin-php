<?php

namespace BitWasp\Bitcoin\Serializer\Transaction;

use BitWasp\Bitcoin\Parser;
use BitWasp\Bitcoin\Buffer;
use BitWasp\Bitcoin\Script\Script;
use BitWasp\Bitcoin\Transaction\TransactionInput;
use BitWasp\Bitcoin\Transaction\TransactionInputInterface;

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
     * @throws \BitWasp\Bitcoin\Exceptions\ParserOutOfRange
     */
    public function fromParser(Parser & $parser)
    {
        return new TransactionInput(
            $parser->readBytes(32, true)->getHex(),
            $parser->readBytes(4, true)->getInt(),
            new Script($parser->getVarString()),
            $parser->readBytes(4, true)->getInt()
        );
    }

    /**
     * @param $string
     * @return TransactionInput
     * @throws \BitWasp\Bitcoin\Exceptions\ParserOutOfRange
     */
    public function parse($string)
    {
        $parser = new Parser($string);
        $input = $this->fromParser($parser);
        return $input;
    }
}
