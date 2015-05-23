<?php

namespace BitWasp\Bitcoin\Serializer\Transaction;

use BitWasp\Buffertools\Parser;
use BitWasp\Buffertools\Buffer;
use BitWasp\Bitcoin\Script\Script;
use BitWasp\Bitcoin\Transaction\TransactionOutput;
use BitWasp\Bitcoin\Transaction\TransactionOutputInterface;
use BitWasp\Buffertools\TemplateFactory;

class TransactionOutputSerializer
{
    /**
     * @return \BitWasp\Buffertools\Template
     */
    private function getTemplate()
    {
        return (new TemplateFactory())
            ->uint64le()
            ->varstring()
            ->getTemplate();
    }

    /**
     * @param TransactionOutputInterface $output
     * @return Buffer
     */
    public function serialize(TransactionOutputInterface $output)
    {
        return $this->getTemplate()->write([
            $output->getValue(),
            $output->getScript()->getBuffer()
        ]);
    }

    /**
     * @param Parser $parser
     * @return TransactionOutput
     * @throws \BitWasp\Buffertools\Exceptions\ParserOutOfRange
     */
    public function fromParser(Parser & $parser)
    {
        $parse = $this->getTemplate()->parse($parser);
        /** @var int|string $value */
        $value = $parse[0];
        /** @var Buffer $scriptBuf */
        $scriptBuf = $parse[1];

        return new TransactionOutput(
            $value,
            new Script($scriptBuf)
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
        return $this->fromParser($parser);
    }
}
