<?php

namespace BitWasp\Bitcoin\Key\Deterministic;

use BitWasp\Bitcoin\Crypto\EcAdapter\Adapter\EcAdapterInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\Key\KeyInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\Key\PrivateKeyInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\Key\PublicKeyInterface;
use BitWasp\Bitcoin\Crypto\Hash;
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
    private $masterPrivate;

    /**
     * @var PublicKeyInterface
     */
    private $masterPublic;

    /**
     * @param EcAdapterInterface $ecAdapter
     * @param KeyInterface $masterKey
     */
    public function __construct(EcAdapterInterface $ecAdapter, KeyInterface $masterKey)
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

        $this->ecAdapter = $ecAdapter;
    }

    /**
     * @return PrivateKeyInterface
     */
    public function getMasterPrivateKey()
    {
        if (null === $this->masterPrivate) {
            throw new \RuntimeException("Cannot produce master private key from master public key");
        }

        return $this->masterPrivate;
    }

    /**
     * @return PublicKeyInterface
     */
    public function getMasterPublicKey()
    {
        return $this->masterPublic;
    }

    /**
     * @return Buffer
     */
    public function getMPK()
    {
        return $this->getMasterPublicKey()->getBuffer()->slice(1);
    }

    /**
     * @param int $sequence
     * @param bool $change
     * @return \GMP
     */
    public function getSequenceOffset($sequence, $change = false)
    {
        $seed = new Buffer(sprintf("%s:%s:%s", $sequence, $change ? '1' : '0', $this->getMPK()->getBinary()), null, $this->ecAdapter->getMath());
        return Hash::sha256d($seed)->getGmp();
    }

    /**
     * @param int $sequence
     * @param bool $change
     * @return PrivateKeyInterface|PublicKeyInterface
     */
    public function deriveChild($sequence, $change = false)
    {
        $key = is_null($this->masterPrivate) ? $this->masterPublic : $this->masterPrivate;
        return $key->tweakAdd($this->getSequenceOffset($sequence, $change));
    }
}
