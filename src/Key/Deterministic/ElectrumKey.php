<?php

namespace BitWasp\Bitcoin\Key\Deterministic;

use BitWasp\Bitcoin\Crypto\Hash;
use BitWasp\Bitcoin\Crypto\EcAdapter\Adapter\EcAdapterInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\Key\KeyInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\Key\PrivateKeyInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\Key\PublicKeyInterface;
use BitWasp\Buffertools\Buffer;

class ElectrumKey
{
    /**
     * @var EcAdapterInterface
     */
    private $ecAdapter;

    /**
     * @var null|PrivateKeyInterface
     */
    private $masterKey;

    /**
     * @var PublicKeyInterface
     */
    private $publicKey;

    /**
     * @param EcAdapterInterface $ecAdapter
     * @param KeyInterface $masterKey
     */
    public function __construct(EcAdapterInterface $ecAdapter, KeyInterface $masterKey)
    {
        if ($masterKey->isCompressed()) {
            throw new \RuntimeException('Electrum keys are not compressed');
        }

        $this->ecAdapter = $ecAdapter;
        if ($masterKey instanceof PrivateKeyInterface) {
            $this->masterKey = $masterKey;
            $masterKey = $this->masterKey->getPublicKey();
        }

        $this->publicKey = $masterKey;
    }

    /**
     * @return KeyInterface|PrivateKeyInterface|PublicKeyInterface
     */
    public function getMasterPrivateKey()
    {
        if (null === $this->masterKey) {
            throw new \RuntimeException("Cannot produce master private key from master public key");
        }

        return $this->masterKey;
    }

    /**
     * @return PublicKeyInterface
     */
    public function getMasterPublicKey()
    {
        return $this->publicKey;
    }

    /**
     * @return Buffer
     */
    public function getMPK()
    {
        return $this->getMasterPublicKey()->getBuffer()->slice(1);
    }

    /**
     * @param int|string $sequence
     * @param bool $change
     * @return int|string
     */
    public function getSequenceOffset($sequence, $change = false)
    {
        return Hash::sha256d(new Buffer(
            sprintf(
                "%s:%s:%s",
                $sequence,
                $change ? '1' : '0',
                $this->getMPK()->getBinary()
            ),
            null,
            $this->ecAdapter->getMath()
        ))->getInt();
    }

    /**
     * @param int|string $sequence
     * @param bool $change
     * @return PrivateKeyInterface|PublicKeyInterface
     */
    public function deriveChild($sequence, $change = false)
    {
        return $this->publicKey->tweakAdd($this->getSequenceOffset($sequence, $change));
    }
}
