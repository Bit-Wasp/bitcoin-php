<?php

namespace BitWasp\Bitcoin\Test\Network\Messages;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Crypto\Random\Random;
use BitWasp\Bitcoin\Network\MessageFactory;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Buffertools\Parser;

class FilterClearTest extends AbstractTestCase
{
    public function testNetworkSerializable()
    {

        $factory = new MessageFactory(Bitcoin::getDefaultNetwork(), new Random());
        $filterclear = $factory->filterclear();

        $serialized = $filterclear->getNetworkMessage()->getBuffer();
        $parsed = $factory->parse(new Parser($serialized))->getPayload();

        $this->assertEquals($parsed, $filterclear);
    }
}
