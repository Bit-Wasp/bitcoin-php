<?php

namespace BitWasp\Bitcoin\MessageSigner;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Serializer\MessageSigner\SignedMessageSerializer;
use BitWasp\Bitcoin\Crypto\EcAdapter\Impl\PhpEcc\Serializer\Signature\CompactSignatureSerializer;
use BitWasp\Bitcoin\Crypto\EcAdapter\Impl\PhpEcc\Signature\CompactSignature;

class SignedMessage
{

    /**
     * @var string
     */
    private $message;

    /**
     * @var CompactSignature
     */
    private $compactSignature;

    /**
     * @param string $message
     * @param CompactSignature $signature
     */
    public function __construct($message, CompactSignature $signature)
    {
        $this->message = $message;
        $this->compactSignature = $signature;
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @return CompactSignature
     */
    public function getCompactSignature()
    {
        return $this->compactSignature;
    }

    /**
     * @return \BitWasp\Buffertools\Buffer
     */
    public function getBuffer()
    {
        $serializer = new SignedMessageSerializer(new CompactSignatureSerializer(Bitcoin::getMath()));
        return $serializer->serialize($this);
    }
}
