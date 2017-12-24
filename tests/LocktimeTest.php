<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Tests;

use BitWasp\Bitcoin\Locktime;

class LocktimeTest extends AbstractTestCase
{

    public function testToTimestamp()
    {
        $nTime = 1951606400;
        $locktime = new Locktime();
        $timestamp = $locktime->toTimestamp($nTime);
        $this->assertEquals($nTime - Locktime::BLOCK_MAX, $timestamp);
    }

    public function testFromTimestamp()
    {
        $timestamp = 1451606400;
        $locktime = new Locktime();
        $nTime = $locktime->fromTimestamp($timestamp);
        $this->assertEquals($timestamp, ($nTime - Locktime::BLOCK_MAX));
    }

    public function testFromBlockHeight()
    {
        $height = 101011;
        $locktime = new Locktime();
        $this->assertEquals($height, $locktime->fromBlockHeight($height));
    }

    public function testToBlockHeight()
    {
        $height = $nTime = 999999;
        $locktime = new Locktime();
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
        $locktime = new Locktime();

        // One under the maximum
        $allowed = Locktime::TIME_MAX;

        $nTime = $locktime->fromTimestamp($allowed);
        $this->assertEquals(Locktime::INT_MAX, $nTime);

        $disallowed = $allowed + 1;
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
        $locktime = new Locktime();

        $allowed = Locktime::INT_MAX;
        $timestamp = $locktime->toTimestamp($allowed);
        $this->assertEquals(Locktime::TIME_MAX, $timestamp);

        $disallowed = $allowed + 1;
        $locktime->toTimestamp($disallowed);
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Lock time out of range for timestamp
     */
    public function testToTimeStampButTooLow()
    {
        $locktime = new Locktime();
        $locktime->toTimestamp(1);
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage This block height is too high
     */
    public function testFromBlockHeightTooHigh()
    {
        $locktime = new Locktime();
        $disallowed = Locktime::BLOCK_MAX + 1;
        $locktime->fromBlockHeight($disallowed);
    }

    /**
     * @expectedException \Exception
     * @expcetedExceptionMessage This locktime is out of range for a block height
     */
    public function testToBlockHeightF()
    {
        $locktime = new Locktime();

        $allowed = Locktime::BLOCK_MAX;
        $this->assertEquals($allowed, $locktime->toBlockHeight($allowed));

        $disallowed = $allowed + 1;
        $locktime->toBlockHeight($disallowed);
    }
}
