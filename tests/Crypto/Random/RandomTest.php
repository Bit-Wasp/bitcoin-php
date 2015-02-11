<?php

namespace Bitcoin\Tests\Crypto\Random;

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
        $random = new \Bitcoin\Crypto\Random\Random;
        $bytes  = $random->bytes(32);
        $this->assertInstanceOf($this->bufferType, $bytes);
        $this->assertEquals(32, $bytes->getSize());
    }
}
