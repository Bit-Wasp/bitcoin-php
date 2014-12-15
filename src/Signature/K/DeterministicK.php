<?php

namespace Bitcoin\Signature\K;

use Bitcoin\Util\Buffer;
use Bitcoin\Crypto\DRBG\HMACDRBG;
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
     * @param Buffer $data
     * @param string $algo
     * @param GeneratorPoint $generator
     */
    public function __construct(PrivateKeyInterface $privateKey, Buffer $data, $algo = 'sha256', GeneratorPoint $generator = null)
    {
        $entropy    = $privateKey->serialize() . $data->serialize();
        $this->drbg = new HMACDRBG($algo, $entropy, null, 256, $generator);
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
