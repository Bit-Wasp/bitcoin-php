<?php

namespace Bitcoin\Crypto\Random;

use Bitcoin\Buffer;
use Bitcoin\Key\PrivateKeyInterface;
use Bitcoin\Math\Math;
use Mdanter\Ecc\GeneratorPoint;

class Rfc6979 implements RbgInterface
{

    /**
     * @var HmacDrbg
     */
    protected $drbg;

    /**
     * @var Math
     */
    protected $math;

    /**
     * @var GeneratorPoint
     */
    protected $generator;

    /**
     * @var Buffer
     */
    protected $k;

    /**
     * @param PrivateKeyInterface $privateKey
     * @param Buffer $messageHash
     * @param string $algo
     */
    public function __construct(Math $math, GeneratorPoint $generator, PrivateKeyInterface $privateKey, Buffer $messageHash, $algo = 'sha256')
    {
        $this->math      = $math;
        $this->generator = $generator;
        $entropy         = new Buffer($privateKey->serialize() . $messageHash->serialize());
        $this->drbg      = new HmacDrbg($algo, $entropy);
        return $this;
    }

    /**
     * Return a K value deterministically derived from the private key
     *  and data
     *
     * @return Buffer
     */
    public function bytes($numBytes)
    {
        if (is_null($this->k)) {
            while (true) {
                $this->k    = $this->drbg->bytes($numBytes);
                $kInt = $this->k->serialize('int');

                // Check k is between [1, ... Q]
                if ($this->math->cmp(1, $kInt) <= 0 && $this->math->cmp($kInt, $this->generator->getOrder()) < 0) {
                    break;
                }

                // Otherwise derive another and try again.
                $this->drbg->update(null);
            }
        }

        return $this->k;
    }
}
