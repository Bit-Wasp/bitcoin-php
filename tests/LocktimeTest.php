<?php

namespace BitWasp\Bitcoin\Tests;

use BitWasp\Bitcoin\Math\Math;
use BitWasp\Bitcoin\Locktime;

class LocktimeTest extends AbstractTestCase
{

    public function testToTimestamp()
    {
        $nTime = '1951606400';
        $locktime = new Locktime(new Math());
        $timestamp = $locktime->toTimestamp($nTime);
        $this->assertEquals($nTime - Locktime::BLOCK_MAX, $timestamp);
    }

    public function testFromTimestamp()
    {
        $timestamp = '1451606400';
        $locktime = new Locktime(new Math());
        $nTime = $locktime->fromTimestamp($timestamp);
        $this->assertEquals($timestamp, ($nTime - Locktime::BLOCK_MAX));
    }

    public function testFromBlockHeight()
    {
        $height = '101011';
        $locktime = new Locktime(new Math());
        $this->assertEquals($height, $locktime->fromBlockHeight($height));
    }

    public function testToBlockHeight()
    {
        $height = $nTime = '999999';
        $locktime = new Locktime(new Math());
        $this->assertEquals($height, $locktime->toBlockHeight($nTime));
    }

    /**
     * Test that fromTimestamp rejects timestamps that exceed the max (0xffffffff - 500000000)
     *
     * @expectedException \Exception
     * @expectedExceptionMessage Timestamp out of range
     */
    public function testMaxFromTimestamp()
    {
        $math= new Math();
        $locktime = new Locktime($math);

        // One under the maximum
        $allowed = Locktime::TIME_MAX;

        $nTime = $locktime->fromTimestamp($allowed);
        $this->assertEquals(Locktime::INT_MAX, $nTime);

        $disallowed = $math->add($allowed, 1);
        $locktime->fromTimestamp($disallowed);

    }

    /**
     * Test that toTimestamp accepts the maximum locktime int, 0xffffffff,
     * but rejects anything higher
     *
     * @expectedException \Exception
     * @expectedExceptionMessage Lock time too large
     */
    public function testMaxToTimestamp()
    {
        $math = new Math();
        $locktime = new Locktime($math);

        $allowed = Locktime::INT_MAX;
        $timestamp = $locktime->toTimestamp($allowed);
        $this->assertEquals(Locktime::TIME_MAX, $timestamp);

        $disallowed = $math->add($allowed, 1);
        $locktime->toTimestamp($disallowed);
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Lock time out of range for timestamp
     */
    public function testToTimeStampButTooLow()
    {
        $math = new Math();
        $locktime = new Locktime($math);

        $locktime->toTimestamp(1);
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage This block height is too high
     */
    public function testFromBlockHeightTooHigh()
    {
        $math = new Math();
        $locktime = new Locktime($math);

        $disallowed = $math->add(Locktime::BLOCK_MAX, 1);
        $locktime->fromBlockHeight($disallowed);
    }

    /**
     * @expectedException \Exception
     * @expcetedExceptionMessage This locktime is out of range for a block height
     */
    public function testToBlockHeightF()
    {
        $math = new Math();
        $locktime = new Locktime($math);

        $allowed = Locktime::BLOCK_MAX;
        $this->assertEquals($allowed, $locktime->toBlockHeight($allowed));

        $disallowed = $math->add($allowed, 1);
        $locktime->toBlockHeight($disallowed);
    }
}
