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
 * Todo: refactor so this class accepts an initialized DRBGInterface
 */
class DeterministicK implements KInterface
{

    /**
     * @var HMACDRBG
     */
    protected $drbg;

    /**
     * @var Buffer
     */
    protected $k;

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
        if (is_null($this->k)) {
            $this->k = $this->drbg->bytes(32);
        }

        return $this->k;
    }
}
