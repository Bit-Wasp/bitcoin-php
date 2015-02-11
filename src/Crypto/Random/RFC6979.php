<?php

namespace Bitcoin\Crypto\Random;

use Bitcoin\Bitcoin;
use Bitcoin\Buffer;
use Bitcoin\Crypto\Hash;
use Bitcoin\Key\PrivateKeyInterface;
use Bitcoin\Math\Math;
use Mdanter\Ecc\GeneratorPoint;

/**
 * Class RFC6979 - This class is used to derive a deterministic nonce from
 * a private key and a message.
 *
 * @package Bitcoin\Crypto\DRBG
 * @author Thomas Kerin
 */
class RFC6979 implements RBGInterface
{
    /**
     * @var HMACDRBG
     */
    private $drbg;

    /**
     * @var GeneratorPoint
     */
    private $generator;

    /**
     * @var Math
     */
    private $math;

    /**
     * @param $algo
     * @param PrivateKeyInterface $privateKey
     * @param \Bitcoin\Buffer $message
     */
    public function __construct(Math $math, $generator, $privateKey, Buffer $message, $algo = 'sha256')
    {
        $this->generator = $generator;
        $this->math      = $math;
        $entropy         = new Buffer($privateKey->serialize() . Hash::sha256($message, true));
        $this->generator = Bitcoin::getGenerator();
        $this->drbg      = new HMACDRBG($algo, $entropy);
    }

    /**
     * Return a sequence of $numBytes bytes, which are between [1..Q]
     * @param int $numBytes
     * @return \Bitcoin\Buffer
     */
    public function bytes($numBytes)
    {
        $math = Bitcoin::getMath();

        while (true) {
            $k    = $this->drbg->bytes($numBytes);
            $kInt = $k->serialize('int');

            // Check k is between [1, ... Q]
            if ($math->cmp(1, $kInt) <= 0
                and $math->cmp($kInt, $this->generator->getOrder()) < 0) {
                break;
            }

            // Otherwise derive another and try again.
            $this->drbg->update(null);
        }

        return $k;
    }
}
