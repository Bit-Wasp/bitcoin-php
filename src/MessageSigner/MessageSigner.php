<?php

namespace BitWasp\Bitcoin\MessageSigner;

use BitWasp\Bitcoin\Address\PayToPubKeyHashAddress;
use BitWasp\Bitcoin\Crypto\EcAdapter\EcAdapterInterface;
use BitWasp\Bitcoin\Crypto\Hash;
use BitWasp\Bitcoin\Crypto\Random\Rfc6979;
use BitWasp\Bitcoin\Key\PrivateKeyInterface;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\Buffertools;

class MessageSigner
{
    /**
     * @var EcAdapterInterface
     */
    private $ecAdapter;

    /**
     * @param EcAdapterInterface $ecAdapter
     */
    public function __construct(EcAdapterInterface $ecAdapter)
    {
        $this->ecAdapter = $ecAdapter;
    }

    /**
     * @param string $message
     * @return \BitWasp\Buffertools\Buffer
     */
    public function calculateMessageHash($message)
    {
        $content = new Buffer("\x18Bitcoin Signed Message:\n" . Buffertools::numToVarInt(strlen($message))->getBinary() . $message);
        $hash = Hash::sha256d($content);
        return $hash;
    }

    /**
     * @param SignedMessage $signedMessage
     * @param PayToPubKeyHashAddress $address
     * @return bool
     */
    public function verify(SignedMessage $signedMessage, PayToPubKeyHashAddress $address)
    {
        $hash = $this->calculateMessageHash($signedMessage->getMessage());

        $publicKey = $this->ecAdapter->recoverCompact(
            $hash,
            $signedMessage->getCompactSignature()
        );

        return ($publicKey->getAddress()->getHash() == $address->getHash());
    }

    /**
     * @param string $message
     * @param PrivateKeyInterface $privateKey
     * @return SignedMessage
     */
    public function sign($message, PrivateKeyInterface $privateKey)
    {
        $hash = $this->calculateMessageHash($message);

        return new SignedMessage(
            $message,
            $this->ecAdapter->signCompact(
                $hash,
                $privateKey,
                new Rfc6979(
                    $this->ecAdapter,
                    $privateKey,
                    $hash,
                    'sha256'
                )
            )
        );
    }
}
