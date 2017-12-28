<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Tests\Chain;

use BitWasp\Bitcoin\Chain\BlockLocator;
use BitWasp\Bitcoin\Serializer\Chain\BlockLocatorSerializer;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Buffertools\Buffer;

class BlockLocatorTest extends AbstractTestCase
{
    public function testCreate()
    {
        $hash1 = new Buffer('A', 32);
        $hash2 = new Buffer('B', 32);
        $hashStop = new Buffer('', 32);
        $locator = new BlockLocator([$hash1, $hash2], $hashStop);

        $this->assertEquals([$hash1, $hash2], $locator->getHashes());
        $this->assertEquals($hashStop, $locator->getHashStop());
    }

    public function testsSerializer()
    {
        $hash1 = new Buffer(str_pad('', 32, 'A'), 32);
        $hash2 = new Buffer(str_pad('', 32, 'A'), 32);
        $hashStop = new Buffer(str_pad('', 32, '0'), 32);
        $locator = new BlockLocator([$hash1, $hash2], $hashStop);

        $serializer = new BlockLocatorSerializer();
        $buffer = $serializer->serialize($locator);
        $this->assertEquals($buffer->getBinary(), $locator->getBinary());

        $parsed = $serializer->parse($buffer);
        $this->assertEquals($locator, $parsed);
    }
}
