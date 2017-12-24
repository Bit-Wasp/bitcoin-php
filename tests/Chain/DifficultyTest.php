<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Tests\Chain;

use BitWasp\Bitcoin\Chain\Params;
use BitWasp\Bitcoin\Chain\ProofOfWork;
use BitWasp\Bitcoin\Tests\AbstractTestCase;

class DifficultyTest extends AbstractTestCase
{

    public function getLowestBits()
    {
        return 0x1d00ffff;
    }

    public function testGetWork()
    {
        $vectors = [
            [
                0x1d00ffff,
                '4295032833'
            ]
        ];

        $math = $this->safeMath();
        $params = new Params($math);
        $difficulty = new ProofOfWork($math, $params);

        foreach ($vectors as $v) {
            $this->assertEquals($v[1], $math->toString($difficulty->getWork($v[0])));
        }
    }

    public function testGetTarget()
    {
        $json = json_decode($this->dataFile('difficulty.json'));

        $math = $this->safeMath();
        $params = new Params($math);
        $difficulty = new ProofOfWork($math, $params);
        foreach ($json->test as $test) {
            $bits = hexdec($test->bits);

            $this->assertEquals($test->targetHash, $difficulty->getTargetHash($bits)->getHex());
            $this->assertEquals($test->difficulty, $difficulty->getDifficulty($bits));
        }
    }
}
