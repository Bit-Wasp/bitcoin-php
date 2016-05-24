<?php

namespace BitWasp\Bitcoin\Tests\PaymentProtocol;

use BitWasp\Bitcoin\Tests\AbstractTestCase;

abstract class Bip70Test extends AbstractTestCase
{
    /**
     * @return string
     */
    public function getCert()
    {
        return $this->dataPath('ssl/server.crt');
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return $this->dataPath('ssl/server.key');
    }
}
