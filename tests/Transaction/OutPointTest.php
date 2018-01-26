<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Tests\Transaction;

use BitWasp\Bitcoin\Exceptions\InvalidHashLengthException;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Bitcoin\Transaction\OutPoint;
use BitWasp\Buffertools\Buffer;

class OutPointTest extends AbstractTestCase
{
    /**
     * @expectedException InvalidHashLengthException
     * @expectedExceptionMessage OutPoint: hashPrevOut must be a 32-byte Buffer
     */
    public function testInvalidHashSize()
    {
        $this->expectException(InvalidHashLengthException::class);
        $this->expectExceptionMessage("OutPoint: hashPrevOut must be a 32-byte Buffer");

        new OutPoint(new Buffer('', 8), 1);
    }

    public function testOutPoint()
    {
        $txid = new Buffer('a', 32);
        $vout = 10;
        $outpoint = new OutPoint($txid, $vout);
        $this->assertEquals($txid, $outpoint->getTxId());
        $this->assertEquals($vout, $outpoint->getVout());
    }

    public function testCompare()
    {
        $txidA = Buffer::hex('41', 32);
        $txidB = Buffer::hex('42', 32);

        $outPoint1a = new OutPoint($txidA, 0);
        $outPoint1b = new OutPoint($txidA, 1);

        $this->assertFalse($outPoint1a->equals($outPoint1b));

        $outPoint2a = new OutPoint($txidA, 0);
        $outPoint2b = new OutPoint($txidB, 0);

        $this->assertFalse($outPoint2a->equals($outPoint2b));

        $outPoint3a = new OutPoint($txidA, 0);
        $outPoint3b = new OutPoint($txidA, 0);

        $this->assertTrue($outPoint3a->equals($outPoint3b));
    }
}
