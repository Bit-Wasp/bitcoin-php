<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Key\Deterministic;

use BitWasp\Bitcoin\Crypto\EcAdapter\Key\KeyInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\Key\PrivateKeyInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\Key\PublicKeyInterface;
use BitWasp\Bitcoin\Crypto\Hash;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\BufferInterface;

class ElectrumKey
{
    /**
     * @var null|PrivateKeyInterface
     */
    private $masterPrivate;

    /**
     * @var PublicKeyInterface
     */
    private $masterPublic;

    /**
     * @param KeyInterface $masterKey
     */
    public function __construct(KeyInterface $masterKey)
    {
        if ($masterKey->isCompressed()) {
            throw new \RuntimeException('Electrum keys are not compressed');
        }

        if ($masterKey instanceof PrivateKeyInterface) {
            $this->masterPrivate = $masterKey;
            $this->masterPublic = $masterKey->getPublicKey();
        } elseif ($masterKey instanceof PublicKeyInterface) {
            $this->masterPublic = $masterKey;
        }
    }

    /**
     * @return PrivateKeyInterface
     */
    public function getMasterPrivateKey(): PrivateKeyInterface
    {
        if (null === $this->masterPrivate) {
            throw new \RuntimeException("Cannot produce master private key from master public key");
        }

        return $this->masterPrivate;
    }

    /**
     * @return PublicKeyInterface
     */
    public function getMasterPublicKey(): PublicKeyInterface
    {
        return $this->masterPublic;
    }

    /**
     * @return BufferInterface
     */
    public function getMPK(): BufferInterface
    {
        return $this->getMasterPublicKey()->getBuffer()->slice(1);
    }

    /**
     * @param int $sequence
     * @param bool $change
     * @return \GMP
     */
    public function getSequenceOffset(int $sequence, bool $change = false): \GMP
    {
        $seed = new Buffer(sprintf("%s:%d:%s", $sequence, $change ? 1 : 0, $this->getMPK()->getBinary()));
        return Hash::sha256d($seed)->getGmp();
    }

    /**
     * @param int $sequence
     * @param bool $change
     * @return KeyInterface
     */
    public function deriveChild(int $sequence, bool $change = false): KeyInterface
    {
        $key = is_null($this->masterPrivate) ? $this->masterPublic : $this->masterPrivate;
        return $key->tweakAdd($this->getSequenceOffset($sequence, $change));
    }

    /**
     * @return ElectrumKey
     */
    public function withoutPrivateKey(): ElectrumKey
    {
        $clone = clone $this;
        $clone->masterPrivate = null;
        return $clone;
    }
}
