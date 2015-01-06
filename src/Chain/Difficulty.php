<?php
/**
 * Created by PhpStorm.
 * User: thomas
 * Date: 30/12/14
 * Time: 13:42
 */

namespace Bitcoin\Chain;

use Bitcoin\Bitcoin;
use Bitcoin\Util\Buffer;

class Difficulty
{
    const MAX_TARGET = '1d00ffff';

    /**
     * @var Buffer
     */
    protected $bits;

    /**
     * @var \Mdanter\Ecc\MathAdapter
     */
    protected $math;

    /**
     * @param Buffer $bits
     */
    public function __construct(Buffer $bits)
    {
        $this->bits = $bits;
        $this->math = Bitcoin::getMath();
    }

    public function getMaxTarget()
    {

    }

    public function getTarget()
    {
        $bitStr = $this->bits->serialize();
        $sci    = unpack('H2exp/H6mul', $bitStr);

        $target = $this->math->mul(
            $this->math->hexDec($sci['mul']),
            $this->math->pow(
                2,
                $this->math->mul(
                    8,
                    $this->math->sub(
                        $this->math->hexDec($sci['exp']),
                        '3'
                    )
                )
            )
        );

        return $target;

    }
}
