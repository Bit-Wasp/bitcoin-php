<?php

namespace Bitcoin\Signature\K;

use Bitcoin\Util\Buffer;
use Bitcoin\Crypto\Hash;
use Bitcoin\Crypto\DRBG\HMACDRBG;
use Bitcoin\Crypto\DRBG\RFC6979;
use Bitcoin\Key\PrivateKeyInterface;
use Mdanter\Ecc\GeneratorPoint;

/**
 * Class DeterministicK
 * @package Bitcoin\Signature\K
 * @author Thomas Kerin
 */
class DeterministicK implements KInterface
{

    /**
     * @var HMACDRBG
     */
    protected $drbg;

    /**
     * @param PrivateKeyInterface $privateKey
     * @param Buffer $messageHash
     * @param string $algo
     * @param GeneratorPoint $generator
     */
    public function __construct(PrivateKeyInterface $privateKey, Buffer $messageHash, $algo = 'sha256')
    {
        $entropy         = new Buffer($privateKey->serialize() . $messageHash->serialize());
        $this->drbg      = new HMACDRBG($algo, $entropy);
    }

    /**
     * Return a K value deterministically derived from the private key
     *  and data
     */
    public function getK()
    {
        $deterministicK = $this->drbg->bytes(32);
        return $deterministicK;
    }
}
