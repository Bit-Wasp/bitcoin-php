<?php

namespace BitWasp\Bitcoin\Key;

use BitWasp\Bitcoin\Crypto\Hash;
use BitWasp\Bitcoin\Crypto\EcAdapter\EcAdapterInterface;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\Buffertools;

class ElectrumKey
{
    /**
     * @var EcAdapterInterface
     */
    private $ecAdapter;

    /**
     * @var PrivateKeyInterface|PublicKeyInterface
     */
    private $masterKey;

    /**
     * @param EcAdapterInterface $ecAdapter
     * @param KeyInterface|PrivateKeyInterface|PublicKeyInterface $masterKey
     */
    public function __construct(EcAdapterInterface $ecAdapter, KeyInterface $masterKey)
    {
        $this->ecAdapter = $ecAdapter;
        $this->masterKey = $masterKey;
    }

    /**
     * @return KeyInterface|PrivateKeyInterface|PublicKeyInterface
     */
    public function getMasterPrivateKey()
    {
        if ($this->masterKey->isPrivate()) {
            return $this->masterKey;
        }

        throw new \RuntimeException("Cannot produce master private key from master public key");
    }

    /**
     * @return Buffer
     */
    public function getMasterPrivateKeyBuf()
    {
        $private = $this->getMasterPrivateKey();
        return $private->getBuffer();
    }

    /**
     * @return PublicKeyInterface
     */
    public function getMasterPublicKey()
    {
        $key = $this->masterKey;

        return $key instanceof PrivateKeyInterface
            ? $this->masterKey->getPublicKey()
            : $this->masterKey;
    }

    /**
     * @return Buffer
     */
    public function getMasterPublicKeyBuf()
    {
        $math = $this->ecAdapter->getMath();
        $point = $this->getMasterPublicKey()->getPoint();

        return Buffertools::concat(
            Buffer::hex($math->decHex($point->getX()), 32),
            Buffer::hex($math->decHex($point->getY()), 32)
        );
    }

    /**
     * @param $sequence
     * @param bool $change
     * @return int|string
     */
    public function getSequenceOffset($sequence, $change = false)
    {
        return Hash::sha256d(
            new Buffer(
                sprintf(
                    "%s:%s:%s",
                    $sequence,
                    $change ? '1' : '0',
                    $this->getMasterPublicKeyBuf()->getBinary()
                )
            )
        )->getInt();
    }

    /**
     * @param $sequence
     * @param bool $change
     * @return PrivateKeyInterface|PublicKeyInterface
     */
    public function deriveChild($sequence, $change = false)
    {
        return $this->masterKey->tweakAdd($this->getSequenceOffset($sequence, $change));
    }
}
