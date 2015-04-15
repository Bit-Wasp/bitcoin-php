<?php

namespace BitWasp\Bitcoin\Key;

use BitWasp\Bitcoin\Math\Math;
use Mdanter\Ecc\Primitives\Point as EcPoint;
use Mdanter\Ecc\Primitives\GeneratorPoint;

class Point extends EcPoint
{
    /**
     * Take X, Y, and a generator point, and we can get what we need!
     *
     * @param Math $math
     * @param GeneratorPoint $generator
     * @param int|string $x
     * @param int|string $y
     */
    public function __construct(Math $math, GeneratorPoint $generator, $x, $y)
    {
        parent::__construct($math, $generator->getCurve(), $x, $y, $generator->getOrder());
    }
}
