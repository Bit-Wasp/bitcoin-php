<?php

namespace Bitcoin\Tests\Util;

class RandomTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var string
     */
    protected $bufferType = 'Bitcoin\Buffer';

    public function setUp()
    {

    }

    public function testBytes()
    {
        $random = new \Bitcoin\Crypto\Random;
        $bytes  = $random->bytes(32);
        $this->assertInstanceOf($this->bufferType, $bytes);
        $this->assertEquals(32, $bytes->getSize());
    }
}
