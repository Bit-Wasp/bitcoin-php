<?php

namespace BitWasp\Bitcoin\Serializer\Signature;

use BitWasp\Bitcoin\Signature\TransactionSignature;
use BitWasp\Buffertools\Parser;

class TransactionSignatureSerializer
{
    /**
     * @var DerSignatureSerializer
     */
    private $sigSerializer;

    /**
     * @param DerSignatureSerializer $sigSerializer
     */
    public function __construct(DerSignatureSerializer $sigSerializer)
    {
        $this->sigSerializer = $sigSerializer;
    }

    /**
     * @param \BitWasp\Bitcoin\Signature\TransactionSignature $txSig
     * @return \BitWasp\Buffertools\Buffer
     */
    public function serialize(\BitWasp\Bitcoin\Signature\TransactionSignature $txSig)
    {
        $sig = $this->sigSerializer->serialize($txSig->getSignature());
        $parser = new Parser($sig->getHex());
        $parser->writeInt(1, $txSig->getHashType());
        $buffer = $parser->getBuffer();
        return $buffer;
    }

    /**
     * @param Parser $parser
     * @return \BitWasp\Bitcoin\Signature\TransactionSignature
     * @throws \BitWasp\Buffertools\Exceptions\ParserOutOfRange
     */
    public function fromParser(Parser & $parser)
    {
        $signature = $this->sigSerializer->fromParser($parser);
        $hashtype = $parser->readBytes(1)->getInt();
        return new TransactionSignature($signature, $hashtype);
    }

    /**
     * @param $string
     * @return TransactionSignature
     */
    public function parse($string)
    {
        $parser = new Parser($string);
        return $this->fromParser($parser);
    }
}
