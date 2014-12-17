<?php

namespace Bitcoin\Crypto\DRBG;

use Bitcoin\Bitcoin;
use Bitcoin\Util\Buffer;
use Bitcoin\Crypto\Hash;
use Bitcoin\Key\PrivateKeyInterface;
use Mdanter\Ecc\GeneratorPoint;

/**
 * Class RFC6979 - This class is used to derive a deterministic nonce from
 * a private key and a message.
 *
 * @package Bitcoin\Crypto\DRBG
 * @author Thomas Kerin
 */
class RFC6979 implements DRBGInterface
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
     * @param $algo
     * @param PrivateKeyInterface $privateKey
     * @param Buffer $message
     */
    public function __construct(PrivateKeyInterface $privateKey, Buffer $message, $algo = 'sha256')
    {
        $entropy         = new Buffer($privateKey->serialize() . Hash::sha256($message, true));
        $this->generator = Bitcoin::getGenerator();
        $this->drbg      = new HMACDRBG($algo, $entropy);
    }

    /**
     * Return a sequence of $numBytes bytes, which are between [1..Q]
     * @param int $numBytes
     * @return Buffer
     */
    public function bytes($numBytes)
    {
        $math = Bitcoin::getMath();

        while (true) {
            $k    = $this->drbg->bytes(32);
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
