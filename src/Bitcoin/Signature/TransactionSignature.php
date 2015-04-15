<?php

namespace BitWasp\Bitcoin\Signature;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Serializer\Signature\DerSignatureSerializer;
use BitWasp\Bitcoin\Serializer\Transaction\TransactionSignatureSerializer;
use BitWasp\Bitcoin\Signature\SignatureInterface;

class TransactionSignature
{
    /**
     * @var SignatureInterface
     */
    private $sig;

    /**
     * @var int|string
     */
    private $hashType;

    /**
     * @param SignatureInterface $signature
     * @param $hashType
     */
    public function __construct(SignatureInterface $signature, $hashType)
    {
        $this->sig = $signature;
        $this->hashType = $hashType;
    }

    /**
     * @return SignatureInterface
     */
    public function getSignature()
    {
        return $this->sig;
    }

    /**
     * @return int|string
     */
    public function getHashType()
    {
        return $this->hashType;
    }

    public function getBuffer()
    {
        $txSigSerializer = new TransactionSignatureSerializer(new DerSignatureSerializer(Bitcoin::getMath()));
        return $txSigSerializer->serialize($this);
    }
}
