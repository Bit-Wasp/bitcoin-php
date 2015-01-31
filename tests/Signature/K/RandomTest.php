<?php

namespace Bitcoin\Tests\Signature\K;

use Bitcoin\Signature\K\RandomK;

class RandomTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var RandomK
     */
    protected $random;

    protected $bufferType;

    public function __construct()
    {
        $this->bufferType = 'Bitcoin\Buffer';
    }

    public function setUp()
    {
        $this->random = new RandomK();
    }

    public function testGetK()
    {
        $k = $this->random->getK();
        $this->assertInstanceOf($this->bufferType, $k);
        $this->assertEquals(32, $k->getSize());
    }
}
