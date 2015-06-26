<?php

namespace BitWasp\Bitcoin\Test\Network\Messages;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Crypto\Random\Random;
use BitWasp\Bitcoin\Flags;
use BitWasp\Bitcoin\Network\BloomFilter;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Buffertools\Buffer;
use BitWasp\Bitcoin\Network\MessageFactory;
use BitWasp\Buffertools\Parser;

class FilterLoadTest extends AbstractTestCase
{
    public function testNetworkSerialize()
    {
        $math = $this->safeMath();
        $factory = new MessageFactory(Bitcoin::getDefaultNetwork(), new Random());

        $filter = BloomFilter::create($math, 10, 0.000001, 0, new Flags(BloomFilter::UPDATE_ALL));
        $filter->insertData(Buffer::hex('04943fdd508053c75000106d3bc6e2754dbcff19'));

        $filterload = $factory->filterload($filter);
        $serialized = $filterload->getNetworkMessage()->getBuffer();
        $parsed = $factory->parse(new Parser($serialized))->getPayload();

        $this->assertEquals($parsed, $filterload);
    }
}
