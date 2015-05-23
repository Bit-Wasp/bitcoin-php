<?php

namespace BitWasp\Bitcoin;

class Flags
{

    /**
     * @var int
     */
    private $flags;

    /**
     * @param $flags
     */
    public function __construct($flags)
    {
        $this->flags = $flags;
    }

    /**
     * @return int
     */
    public function getFlags()
    {
        return $this->flags;
    }

    /**
     * @param $flags
     * @return int
     */
    public function checkFlags($flags)
    {
        return (bool) ($this->flags & $flags);
    }
}
