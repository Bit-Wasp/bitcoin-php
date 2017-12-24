<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Tests\Crypto\Random;

use BitWasp\Bitcoin\Crypto\Random\Random;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Buffertools\Buffer;

class RandomTest extends AbstractTestCase
{
    public function testBytes()
    {
        $random = new Random();
        $bytes  = $random->bytes(32);
        $this->assertInstanceOf(Buffer::class, $bytes);
        $this->assertEquals(32, $bytes->getSize());
    }
}
