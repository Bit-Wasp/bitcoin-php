<?php

namespace Bitcoin\Signature;

/**
 * Class Signature
 * @package Bitcoin\Signature
 * @author Thomas Kerin
 */
class Signature implements SignatureInterface
{
    /**
     * @var int
     */
    protected $r;

    /**
     * @var int
     */
    protected $s;

    /**
     * @param $r
     * @param $s
     */
    public function __construct($r, $s)
    {
        $this->r = $r;
        $this->s = $s;
    }

    /**
     * @inheritdoc
     */
    public function getR()
    {
        return $this->r;
    }

    /**
     * @inheritdoc
     */
    public function getS()
    {
        return $this->s;
    }
}
