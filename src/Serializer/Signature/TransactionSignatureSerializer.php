<?php

namespace BitWasp\Bitcoin\Serializer\Signature;

use BitWasp\Bitcoin\Crypto\EcAdapter\Serializer\Signature\DerSignatureSerializerInterface;
use BitWasp\Bitcoin\Signature\TransactionSignature;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\BufferInterface;
use BitWasp\Buffertools\Parser;

class TransactionSignatureSerializer
{
    /**
     * @var DerSignatureSerializerInterface
     */
    private $sigSerializer;

    /**
     * @param DerSignatureSerializerInterface $sigSerializer
     */
    public function __construct(DerSignatureSerializerInterface $sigSerializer)
    {
        $this->sigSerializer = $sigSerializer;
    }

    /**
     * @param TransactionSignature $txSig
     * @return BufferInterface
     */
    public function serialize(TransactionSignature $txSig)
    {
        return new Buffer($this->sigSerializer->serialize($txSig->getSignature())->getBinary() . pack('C', $txSig->getHashType()));
    }

    /**
     * @param string|BufferInterface $string
     * @return TransactionSignature
     */
    public function parse($string)
    {
        $adapter = $this->sigSerializer->getEcAdapter();
        $math = $adapter->getMath();
        $buffer = (new Parser($string, $math))->getBuffer()->getBinary();

        return new TransactionSignature(
            $adapter,
            $this->sigSerializer->parse(new Buffer(substr($buffer, 0, -1), null, $math)),
            unpack('C', substr($buffer, -1))[1]
        );
    }
}
