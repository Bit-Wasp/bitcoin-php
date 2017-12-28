<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\MessageSigner;

use BitWasp\Bitcoin\Address\PayToPubKeyHashAddress;
use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Crypto\EcAdapter\Adapter\EcAdapterInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\Key\PrivateKeyInterface;
use BitWasp\Bitcoin\Crypto\Hash;
use BitWasp\Bitcoin\Crypto\Random\Rfc6979;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\BufferInterface;
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
    public function __construct(EcAdapterInterface $ecAdapter = null)
    {
        $this->ecAdapter = $ecAdapter ?: Bitcoin::getEcAdapter();
    }

    /**
     * @param string $message
     * @return BufferInterface
     * @throws \Exception
     */
    private function calculateBody(string $message)
    {
        return new Buffer("\x18Bitcoin Signed Message:\n" . Buffertools::numToVarInt(strlen($message))->getBinary() . $message, null);
    }

    /**
     * @param string $message
     * @return BufferInterface
     */
    public function calculateMessageHash(string $message): BufferInterface
    {
        return Hash::sha256d($this->calculateBody($message));
    }

    /**
     * @param SignedMessage $signedMessage
     * @param PayToPubKeyHashAddress $address
     * @return bool
     */
    public function verify(SignedMessage $signedMessage, PayToPubKeyHashAddress $address): bool
    {
        $hash = $this->calculateMessageHash($signedMessage->getMessage());

        $publicKey = $this->ecAdapter->recover(
            $hash,
            $signedMessage->getCompactSignature()
        );

        return $publicKey->getPubKeyHash()->equals($address->getHash());
    }

    /**
     * @param string $message
     * @param PrivateKeyInterface $privateKey
     * @return SignedMessage
     */
    public function sign(string $message, PrivateKeyInterface $privateKey): SignedMessage
    {
        $hash = $this->calculateMessageHash($message);

        return new SignedMessage(
            $message,
            $privateKey->signCompact(
                $hash,
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
