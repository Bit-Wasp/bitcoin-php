<?php

namespace BitWasp\Bitcoin\Key;

use BitWasp\Bitcoin\Crypto\Hash;
use BitWasp\Bitcoin\Crypto\EcAdapter\EcAdapterInterface;
use BitWasp\Buffertools\Buffer;

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
     * @param $sequence
     * @param bool $change
     * @return int|string
     */
    public function getSequenceOffset($sequence, $change = false)
    {
        $offsetBuf = new Buffer("$sequence:"
            . ($change ? '1' : '0')
            . $this->getMasterPublicKey()->getBinary());

        return Hash::sha256d($offsetBuf)->getInt();
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