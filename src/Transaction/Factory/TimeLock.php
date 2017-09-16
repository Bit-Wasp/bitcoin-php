<?php

namespace BitWasp\Bitcoin\Transaction\Factory;


use BitWasp\Bitcoin\Transaction\Factory\ScriptInfo\CheckLocktimeVerify;

class TimeLock
{
    /**
     * @var CheckLocktimeVerify
     */
    private $info;

    /**
     * TimeLock constructor.
     * @param CheckLocktimeVerify $info
     */
    public function __construct($info)
    {
        if (!is_object($info) || get_class($info) !== CheckLocktimeVerify::class) {
            throw new \RuntimeException("Invalid script info for TimeLock, must be CLTV");
        }

        $this->info = $info;
    }

    /**
     * @return CheckLocktimeVerify
     */
    public function getInfo()
    {
        return $this->info;
    }
}
