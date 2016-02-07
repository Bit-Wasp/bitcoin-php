<?php

namespace BitWasp\Bitcoin\Tests\Transaction;

use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Bitcoin\Transaction\OutPoint;
use BitWasp\Buffertools\Buffer;

class OutPointTest extends AbstractTestCase
{
    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage OutPoint: hashPrevOut must be a 32-byte Buffer
     */
    public function testInvalidHashSize()
    {
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
}
