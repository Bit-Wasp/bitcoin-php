<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Serializer\Signature;

use BitWasp\Bitcoin\Crypto\EcAdapter\Serializer\Signature\DerSignatureSerializerInterface;
use BitWasp\Bitcoin\Signature\TransactionSignature;
use BitWasp\Bitcoin\Signature\TransactionSignatureInterface;
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
     * @param TransactionSignatureInterface $txSig
     * @return BufferInterface
     */
    public function serialize(TransactionSignatureInterface $txSig): BufferInterface
    {
        return new Buffer($this->sigSerializer->serialize($txSig->getSignature())->getBinary() . pack('C', $txSig->getHashType()));
    }

    /**
     * @param string|BufferInterface $string
     * @return TransactionSignatureInterface
     */
    public function parse($string): TransactionSignatureInterface
    {
        $adapter = $this->sigSerializer->getEcAdapter();
        $buffer = (new Parser($string))->getBuffer()->getBinary();

        if (strlen($buffer) < 1) {
            throw new \RuntimeException("Empty signature");
        }

        return new TransactionSignature(
            $adapter,
            $this->sigSerializer->parse(new Buffer(substr($buffer, 0, -1))),
            unpack('C', substr($buffer, -1))[1]
        );
    }
}
