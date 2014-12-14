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
        $byteString = \Bitcoin\Util\Random::bytes(32);
        $buffer     = new Buffer($byteString);
        return $buffer;
    }
}
