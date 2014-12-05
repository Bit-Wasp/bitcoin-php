<?php

namespace Bitcoin\SignatureK;

use Bitcoin\SignatureKInterface;
use Bitcoin\PrivateKeyInterface;

/**
 * Class Deterministic
 * @package Bitcoin\SignatureK
 * @author Thomas Kerin
 */
class Deterministic implements SignatureKInterface
{
    protected $privateKey;

    protected $transaction;

    public function __construct(PrivateKeyInterface $privateKey, Transaction $transaction)
    {
        $this->privateKey  = $privateKey;
        $this->transaction = $transaction;
    }

    /**
     * Return a K value deterministically derived from the private key
     *  - TODO
     */
    public function getK()
    {

    }
}
