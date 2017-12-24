<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Tests\Chain;

use BitWasp\Bitcoin\Block\BlockHeader;
use BitWasp\Bitcoin\Block\BlockHeaderInterface;
use BitWasp\Bitcoin\Chain\Params;
use BitWasp\Bitcoin\Chain\ProofOfWork;
use BitWasp\Bitcoin\Math\Math;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Buffertools\Buffer;

class ProofOfWorkTest extends AbstractTestCase
{

    public function getHistoricData()
    {
        $math = $this->safeMath();
        $params = new Params($math);
        $pow = new ProofOfWork(new Math(), $params);
        $data = json_decode($this->dataFile('pow'), true);

        $results = [];
        foreach ($data as $c => $record) {
            list ($height, $hash, $version, $prev, $merkle, $time, $bits, $nonce) = $record;
            $header = new BlockHeader($version, Buffer::hex($prev, 32), Buffer::hex($merkle, 32), (int) $time, (int) Buffer::hex($bits)->getInt(), (int) $nonce);
            $results[] = [$pow, $height, $hash, $header];
        }

        return $results;
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage nBits below minimum work
     */
    public function testWhereBitsBelowMinimum()
    {
        $math = $this->safeMath();
        $params = new Params($math);
        $pow = new ProofOfWork(new Math(), $params);
        $bits = 1;
        $pow->check(Buffer::hex('00000000a3bbe4fd1da16a29dbdaba01cc35d6fc74ee17f794cf3aab94f7aaa0'), $bits);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Hash doesn't match nBits
     */
    public function testWhereHashTooLow()
    {
        $math = new Math();
        $params = new Params($math);
        $pow = new ProofOfWork(new Math(), $params);
        $bits = 0x181287ba;
        $pow->check(Buffer::hex('00000000a3bbe4fd1da16a29dbdaba01cc35d6fc74ee17f794cf3aab94f7aaa0'), $bits);
    }

    /**
     * @dataProvider getHistoricData
     * @param ProofOfWork $pow
     * @param int $height
     * @param string $hash
     * @param BlockHeaderInterface $header
     */
    public function testHistoric(ProofOfWork $pow, $height, $hash, BlockHeaderInterface $header)
    {
        $this->assertTrue($pow->checkHeader($header));
    }
}
