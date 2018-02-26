<?php

namespace BitWasp\Bitcoin\MessageSigner;

use BitWasp\Bitcoin\Address\PayToPubKeyHashAddress;
use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Crypto\EcAdapter\Adapter\EcAdapterInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\Key\PrivateKeyInterface;
use BitWasp\Bitcoin\Crypto\Hash;
use BitWasp\Bitcoin\Crypto\Random\Rfc6979;
use BitWasp\Bitcoin\Network\NetworkInterface;
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
    public function __construct(EcAdapterInterface $ecAdapter = null)
    {
        $this->ecAdapter = $ecAdapter ?: Bitcoin::getEcAdapter();
    }

    /**
     * @param NetworkInterface $network
     * @param string $message
     * @return \BitWasp\Buffertools\BufferInterface
     */
    private function calculateBody(NetworkInterface $network, $message)
    {
        return new Buffer(sprintf(
            "\x18%s:\n%s%s",
            $network->getSignedMessageMagic(),
            Buffertools::numToVarInt(strlen($message))->getBinary(),
            $message
        ));
    }

    /**
     * @param NetworkInterface $network
     * @param string $message
     * @return \BitWasp\Buffertools\BufferInterface
     */
    public function calculateMessageHash(NetworkInterface $network, $message)
    {
        return Hash::sha256d($this->calculateBody($network, $message));
    }

    /**
     * @param SignedMessage $signedMessage
     * @param PayToPubKeyHashAddress $address
     * @param NetworkInterface|null $network
     * @return bool
     */
    public function verify(SignedMessage $signedMessage, PayToPubKeyHashAddress $address, NetworkInterface $network = null)
    {
        $network = $network ?: Bitcoin::getNetwork();
        $hash = $this->calculateMessageHash($network, $signedMessage->getMessage());
        $publicKey = $this->ecAdapter->recover($hash, $signedMessage->getCompactSignature());
        $pubKeyAddress = new PayToPubKeyHashAddress($publicKey->getPubKeyHash());
        return hash_equals($pubKeyAddress->getHash()->getBinary(), $address->getHash()->getBinary());
    }

    /**
     * @param string $message
     * @param PrivateKeyInterface $privateKey
     * @param NetworkInterface|null $network
     * @return SignedMessage
     */
    public function sign($message, PrivateKeyInterface $privateKey, NetworkInterface $network = null)
    {
        $network = $network ?: Bitcoin::getNetwork();
        $hash = $this->calculateMessageHash($network, $message);

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
