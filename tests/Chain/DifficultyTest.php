<?php

namespace BitWasp\Bitcoin\Tests\Chain;

use BitWasp\Bitcoin\Chain\Params;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Buffertools\Buffer;
use BitWasp\Bitcoin\Chain\ProofOfWork;
use Mdanter\Ecc\Math\MathAdapterInterface;

class DifficultyTest extends AbstractTestCase
{

    public function getLowestBits(MathAdapterInterface $math)
    {
        return Buffer::hex('1d00ffff', 4, $math);
    }

    public function testGetWork()
    {
        $vectors = [
            [
                Buffer::hex('1d00ffff'),
                '4295032833'
            ]
        ];

        $math = $this->safeMath();
        $params = new Params($math);
        $difficulty = new ProofOfWork($math, $params);

        foreach ($vectors as $v) {
            $this->assertEquals($v[1], $difficulty->getWork($v[0]));
        }

    }

    public function testGetTarget()
    {
        $f = file_get_contents(__DIR__.'/../Data/difficulty.json');

        $json = json_decode($f);

        $math = $this->safeMath();
        $params = new Params($math);
        $difficulty = new ProofOfWork($math, $params);
        foreach ($json->test as $test) {
            $bits = Buffer::hex($test->bits);

            $this->assertEquals($test->targetHash, $difficulty->getTargetHash($bits)->getHex());
            $this->assertEquals($test->difficulty, $difficulty->getDifficulty($bits));
        }
    }
}
