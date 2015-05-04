<?php

namespace BitWasp\Bitcoin\Serializer\Transaction;

use BitWasp\Buffertools\Parser;
use BitWasp\Buffertools\Buffer;
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
        $script = new Parser();
        $script->writeWithLength($output->getScript()->getBuffer());

        $parser = new Parser(new Buffer(pack("Q", (int)$output->getValue()) . $script->getBuffer()->getBinary()));
        return $parser->getBuffer();
    }

    /**
     * @param Parser $parser
     * @return TransactionOutput
     * @throws \BitWasp\Buffertools\Exceptions\ParserOutOfRange
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
     * @throws \BitWasp\Buffertools\Exceptions\ParserOutOfRange
     */
    public function parse($string)
    {
        $parser = new Parser($string);
        $output = $this->fromParser($parser);
        return $output;
    }
}
