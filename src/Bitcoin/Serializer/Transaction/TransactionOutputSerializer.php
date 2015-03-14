<?php

namespace Afk11\Bitcoin\Serializer\Transaction;

use Afk11\Bitcoin\Parser;
use Afk11\Bitcoin\Buffer;
use Afk11\Bitcoin\Script\Script;
use Afk11\Bitcoin\Transaction\TransactionOutput;
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
        return $parser
            ->writeInt(8, $output->getValue(), true)
            ->writeWithLength($output->getScript()->getBuffer())
            ->getBuffer();
    }

    /**
     * @param Parser $parser
     * @return TransactionOutput
     * @throws \Afk11\Bitcoin\Exceptions\ParserOutOfRange
     */
    public function fromParser(Parser &$parser)
    {
        return new TransactionOutput(
            $parser->readBytes(8, true)->serialize('int'),
            new Script($parser->getVarString())
        );
    }

    /**
     * @param $string
     * @return TransactionOutput
     * @throws \Afk11\Bitcoin\Exceptions\ParserOutOfRange
     */
    public function parse($string)
    {
        $parser = new Parser($string);
        $output = $this->fromParser($parser);
        return $output;
    }
}
