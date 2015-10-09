<?php

namespace BitWasp\Bitcoin\Tests\Chain;

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

        $difficulty = new ProofOfWork($this->math, $this->bits);

        foreach ($vectors as $v) {
            $this->assertEquals($v[1], $difficulty->getWork($v[0]));
        }

    }

    public function testDefaultLowestDifficulty()
    {
        $difficulty = new ProofOfWork($this->math);

        $this->assertEquals($this->getLowestBits($this->math), $difficulty->lowestBits());
        $this->assertEquals($this->math->hexDec($this->targetHash), $difficulty->getMaxTarget());
    }

    public function testLowestDifficulty()
    {
        $difficulty = new ProofOfWork($this->math, $this->bits);

        $this->assertEquals($this->getLowestBits($this->math), $difficulty->lowestBits());
        $this->assertEquals($this->math->hexDec($this->targetHash), $difficulty->getMaxTarget());
    }

    public function testSetLowestDifficulty()
    {
        $bits = Buffer::hex('1e123456');
        $difficulty = new ProofOfWork($this->math, $bits);
        $this->assertEquals($bits, $difficulty->lowestBits());
    }

    public function testGetTarget()
    {
        $f = file_get_contents(__DIR__.'/../Data/difficulty.json');

        $json = json_decode($f);
        foreach ($json->test as $test) {
            $default = Buffer::hex($test->defaultBits);
            $bits = Buffer::hex($test->bits);
            $difficulty = new ProofOfWork($this->math, $default);

            $this->assertEquals($test->targetHash, $difficulty->getTargetHash($bits)->getHex());
            $this->assertEquals($test->difficulty, $difficulty->getDifficulty($bits));
        }
    }
}
