<?php

namespace Bitcoin\Signature\K;

use Bitcoin\Util\Buffer;

/**
 * Class Random
 * @package Bitcoin\SignatureK
 * @author Thomas Kerin
 */
class Random implements KInterface
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
        $buffer = \Bitcoin\Util\Random::bytes(32);

        return $buffer;
    }
}
