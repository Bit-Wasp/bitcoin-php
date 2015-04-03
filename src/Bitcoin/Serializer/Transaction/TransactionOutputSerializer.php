<?php

namespace BitWasp\Bitcoin\Serializer\Transaction;

use BitWasp\Bitcoin\Parser;
use BitWasp\Bitcoin\Buffer;
use BitWasp\Bitcoin\Script\Script;
use BitWasp\Bitcoin\Transaction\TransactionOutput;
use BitWasp\Bitcoin\Transaction\TransactionOutputInterface;

class TransactionOutputSerializer
{
    /**
     * @param TransactionOutputInterface $output
     * @return Buffer
     */
    public function serialize(TransactionOutputInterface $output)
    {
        $parser = new Parser();
        return $parser
            ->writeInt(8, $output->getValue(), true)
            ->writeWithLength($output->getScript()->getBuffer())
            ->getBuffer();
    }

    /**
     * @param Parser $parser
     * @return TransactionOutput
     * @throws \BitWasp\Bitcoin\Exceptions\ParserOutOfRange
     */
    public function fromParser(Parser & $parser)
    {
        return new TransactionOutput(
            $parser->readBytes(8, true)->getInt(),
            new Script($parser->getVarString())
        );
    }

    /**
     * @param $string
     * @return TransactionOutput
     * @throws \BitWasp\Bitcoin\Exceptions\ParserOutOfRange
     */
    public function parse($string)
    {
        $parser = new Parser($string);
        $output = $this->fromParser($parser);
        return $output;
    }
}
