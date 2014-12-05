<?php

namespace Bitcoin;

use Bitcoin\SignatureInterface;

/**
 * Class Signature
 * @package Bitcoin
 * @author  Thomas Kerin
 */
class Signature implements SignatureInterface
{
    protected $r;

    protected $s;

    public function __construct($r, $s)
    {
        $this->r = $r;
        $this->s = $s;
    }

    public function getR()
    {
        return $this->r;
    }

    public function getS()
    {
        return $this->s;
    }
}
