<?php

namespace BitWasp\Bitcoin\Test\Network\Messages;

use BitWasp\Bitcoin\Crypto\Random\Random;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use Mdanter\Ecc\Message\MessageFactory;

class FilterLoadTest extends AbstractTestCase
{
    public function testNetworkSerialize()
    {
        $math = $this->safeMath();
        $factory = new MessageFactory($math, new Random());

    }
}
