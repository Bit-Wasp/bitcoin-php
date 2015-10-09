<?php

namespace BitWasp\Bitcoin\Tests\Chain;

use BitWasp\Bitcoin\Chain\Params;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Buffertools\Buffer;
use BitWasp\Bitcoin\Chain\ProofOfWork;
use Mdanter\Ecc\Math\MathAdapterInterface;

class DifficultyTest extends AbstractTestCase
{
    /**
     * @var \BitWasp\Bitcoin\Math\Math
     */
    protected $math;

    /**
     * @var string
     */
    protected $targetHash = '00000000ffff0000000000000000000000000000000000000000000000000000';

    /**
     * @var Buffer
     */
    protected $bits;

    public function __construct()
    {
        $this->math = $this->safeMath();
    }

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

        $params = new Params();
        $difficulty = new ProofOfWork($this->math, $params);

        foreach ($vectors as $v) {
            $this->assertEquals($v[1], $difficulty->getWork($v[0]));
        }

    }

    public function testGetTarget()
    {
        $f = file_get_contents(__DIR__.'/../Data/difficulty.json');

        $json = json_decode($f);
        foreach ($json->test as $test) {
            $default = Buffer::hex($test->defaultBits);
            $bits = Buffer::hex($test->bits);
            $params = new Params();
            $difficulty = new ProofOfWork($this->math, $params);

            $this->assertEquals($test->targetHash, $difficulty->getTargetHash($bits)->getHex());
            $this->assertEquals($test->difficulty, $difficulty->getDifficulty($bits));
        }
    }
}
