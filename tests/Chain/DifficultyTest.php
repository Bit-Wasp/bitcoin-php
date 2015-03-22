<?php

namespace BitWasp\Bitcoin\Tests\Chain;

use BitWasp\Bitcoin\Buffer;
use BitWasp\Bitcoin\Bitcoin;

class DifficultyTest extends \PHPUnit_Framework_TestCase
{
    protected $math;
    protected $targetHash;
    protected $bits;

    public function __construct()
    {
        $this->math = Bitcoin::getMath();;
        $this->bits = Buffer::hex('1d00ffff');
        $this->targetHash = '00000000ffff0000000000000000000000000000000000000000000000000000';
    }

    public function testDefaultLowestDifficulty()
    {
        $difficulty = new \BitWasp\Bitcoin\Chain\Difficulty($this->math);

        $this->assertEquals($this->bits, $difficulty->lowestBits());
        $this->assertEquals($this->math->hexDec($this->targetHash), $difficulty->getMaxTarget());
    }

    public function testLowestDifficulty()
    {
        $difficulty = new \BitWasp\Bitcoin\Chain\Difficulty($this->math, $this->bits);

        $this->assertEquals($this->bits, $difficulty->lowestBits());
        $this->assertEquals($this->math->hexDec($this->targetHash), $difficulty->getMaxTarget());
    }

    public function testSetLowestDifficulty()
    {
        $bits = Buffer::hex('1e123456');
        $difficulty = new \BitWasp\Bitcoin\Chain\Difficulty($this->math, $bits);
        $this->assertEquals($bits, $difficulty->lowestBits());
    }

    public function testGetTarget()
    {
        $f = file_get_contents(__DIR__.'/../Data/difficulty.json');

        $json = json_decode($f);
        foreach ($json->test as $test) {
            $default = Buffer::hex($test->defaultBits);
            $bits = Buffer::hex($test->bits);
            $difficulty = new \BitWasp\Bitcoin\Chain\Difficulty($this->math, $default);

            $this->assertEquals($test->targetHash, $difficulty->getTargetHash($bits));
            $this->assertEquals($test->difficulty, $difficulty->getDifficulty($bits));
        }
    }
}
