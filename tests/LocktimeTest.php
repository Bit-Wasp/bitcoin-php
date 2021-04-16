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
     */
    public function testMaxFromTimestamp()
    {
        $locktime = new Locktime();

        // One under the maximum
        $allowed = Locktime::TIME_MAX;

        $nTime = $locktime->fromTimestamp($allowed);
        $this->assertEquals(Locktime::INT_MAX, $nTime);

        $disallowed = $allowed + 1;
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Timestamp out of range");
        $locktime->fromTimestamp($disallowed);
    }

    /**
     * Test that toTimestamp accepts the maximum locktime int, 0xffffffff,
     * but rejects anything higher
     *
     */
    public function testMaxToTimestamp()
    {
        $locktime = new Locktime();

        $allowed = Locktime::INT_MAX;
        $timestamp = $locktime->toTimestamp($allowed);
        $this->assertEquals(Locktime::TIME_MAX, $timestamp);

        $disallowed = $allowed + 1;
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Lock time too large");
        $locktime->toTimestamp($disallowed);
    }

    public function testToTimeStampButTooLow()
    {
        $locktime = new Locktime();
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Lock time out of range for timestamp");
        $locktime->toTimestamp(1);
    }

    public function testFromBlockHeightTooHigh()
    {
        $locktime = new Locktime();
        $disallowed = Locktime::BLOCK_MAX + 1;
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("This block height is too high");
        $locktime->fromBlockHeight($disallowed);
    }

    public function testToBlockHeightF()
    {
        $locktime = new Locktime();

        $allowed = Locktime::BLOCK_MAX;
        $this->assertEquals($allowed, $locktime->toBlockHeight($allowed));

        $disallowed = $allowed + 1;
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("This locktime is out of range for a block height");
        $locktime->toBlockHeight($disallowed);
    }
}
