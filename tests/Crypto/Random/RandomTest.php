<?php

namespace BitWasp\Bitcoin\Tests\Crypto\Random;

class RandomTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var string
     */
    protected $bufferType = 'BitWasp\Buffertools\Buffer';

    public function testBytes()
    {
        $random = new \BitWasp\Bitcoin\Crypto\Random\Random;
        $bytes  = $random->bytes(32);
        $this->assertInstanceOf($this->bufferType, $bytes);
        $this->assertEquals(32, $bytes->getSize());
    }
}
