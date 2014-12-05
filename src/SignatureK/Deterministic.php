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
    public function __construct(PrivateKeyInterface $privateKey)
    {
        // Todo
    }

    /**
     * Return a K value deterministically derived from the private key
     */
    public function getK()
    {

    }
} 