<?php

namespace Bitcoin\Tests\Util;

class RandomTest extends \PHPUnit_Framework_TestCase
{

    public function setUp()
    {
    }

    public function testBytes()
    {
        $random = new \Bitcoin\Crypto\Random;
        $bytes  = $random->bytes(32);
        $this->assertInstanceOf('Bitcoin\Util\Buffer', $bytes);
        $this->assertEquals(32, $bytes->getSize());
    }

    /**
     * @expectedException \Bitcoin\Exceptions\RandomBytesFailure
     */
    public function testFailureOrWeak()
    {
        $random = new \Bitcoin\Crypto\Random;
        $bytes  = $random->bytes(-1);
    }

}
