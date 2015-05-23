<?php

namespace BitWasp\Bitcoin\Serializer\Transaction;

use BitWasp\Buffertools\Parser;
use BitWasp\Buffertools\Buffer;
use BitWasp\Bitcoin\Script\Script;
use BitWasp\Bitcoin\Transaction\TransactionInput;
use BitWasp\Bitcoin\Transaction\TransactionInputInterface;
use BitWasp\Buffertools\TemplateFactory;

class TransactionInputSerializer
{
    /**
     * @return \BitWasp\Buffertools\Template
     */
    private function getTemplate()
    {
        return (new TemplateFactory())
            ->bytestringle(32)
            ->uint32le()
            ->varstring()
            ->uint32le()
            ->getTemplate();
    }

    /**
     * @param TransactionInputInterface $input
     * @return Buffer
     */
    public function serialize(TransactionInputInterface $input)
    {
        return $this
            ->getTemplate()
            ->write([
                Buffer::hex($input->getTransactionId()),
                $input->getVout(),
                $input->getScript()->getBuffer(),
                $input->getSequence()
            ]);
    }

    /**
     * @param Parser $parser
     * @return TransactionInput
     * @throws \BitWasp\Buffertools\Exceptions\ParserOutOfRange
     */
    public function fromParser(Parser & $parser)
    {
        $parse = $this->getTemplate()->parse($parser);
        /** @var Buffer $txidBuf */
        $txidBuf = $parse[0];
        /** @var int|string $vout */
        $vout = $parse[1];
        /** @var Buffer $scriptBuf */
        $scriptBuf = $parse[2];
        /** @var int|string $vout */
        $sequence = $parse[3];
        return new TransactionInput(
            $txidBuf->getHex(),
            $vout,
            new Script($scriptBuf),
            $sequence
        );
    }

    /**
     * @param $string
     * @return TransactionInput
     * @throws \BitWasp\Buffertools\Exceptions\ParserOutOfRange
     */
    public function parse($string)
    {
        $parser = new Parser($string);
        return $this->fromParser($parser);
    }
}
