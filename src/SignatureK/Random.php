<?php

namespace Bitcoin\SignatureK;

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
        return \Bitcoin\Util\Random::bytes(32);
    }
}
