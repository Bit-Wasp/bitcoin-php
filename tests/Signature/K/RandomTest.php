<?php

namespace Bitcoin\Tests\Signature\K;

use Bitcoin\Signature\K\Random;

class RandomTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Random
     */
    protected $random;

    protected $bufferType;

    public function __construct()
    {
        $this->bufferType = 'Bitcoin\Util\Buffer';
    }

    public function setUp()
    {
        $this->random = new Random();
    }

    public function testGetK()
    {
        $k = $this->random->getK();
        $this->assertInstanceOf($this->bufferType, $k);
        $this->assertEquals(32, $k->getSize());
    }
}
