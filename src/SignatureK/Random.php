<?php

namespace Bitcoin\SignatureK;

use Bitcoin\Util\Buffer;
use Bitcoin\SignatureKInterface;

/**
 * Class Random
 * @package Bitcoin\SignatureK
 * @author Thomas Kerin
 */
class Random implements SignatureKInterface
{

    /**
     * Return a random K value
     *
     * @return string
     * @throws \Bitcoin\Exceptions\InsufficientEntropy
     * @throws \Exception
     */
    public function getK()
    {
        $byteString = \Bitcoin\Util\Random::bytes(32);
        $buffer     = Buffer::hex($byteString);
        return $buffer;
    }
}
