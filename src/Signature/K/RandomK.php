<?php

namespace Bitcoin\Signature\K;

use Bitcoin\Crypto\Random;

/**
 * Class Random
 * @package Bitcoin\SignatureK
 * @author Thomas Kerin
 */
class RandomK implements KInterface
{

    /**
     * Return a buffer containing a random K value
     *
     * @return string
     * @throws \Bitcoin\Exceptions\InsufficientEntropy
     * @throws \Exception
     */
    public function getK()
    {
        $buffer = Random::bytes(32);

        return $buffer;
    }
}
