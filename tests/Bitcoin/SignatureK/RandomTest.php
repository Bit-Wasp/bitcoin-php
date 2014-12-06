<?php


namespace Bitcoin\SignatureK;


class RandomTest extends \PHPUnit_Framework_TestCase
{
    protected $random;

    public function setUp()
    {
        $this->random = new Random();
    }

    public function testGetK()
    {
        $k = $this->random->getK();
        $this->assertInstanceOf('Bitcoin\Util\Buffer', $k);
        $this->assertEquals(32, $k->getSize());
    }
} 