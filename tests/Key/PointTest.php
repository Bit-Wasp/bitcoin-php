<?php

namespace Bitcoin\Tests\Key;

use Bitcoin\Bitcoin;
use Bitcoin\Key\Point;

class PointTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Point
     */
    protected $point;

    protected $baseType = 'Bitcoin\Key\Point';

    protected $generator;

    public function __construct()
    {
        $this->generator = Bitcoin::getGenerator();
    }

    public function setUp()
    {
        $this->point = null;
    }

    public function testCreatePoint()
    {
        $this->point = new Point(
            $this->generator,
            '94075108042016923119479678483338406049382274483038030215794449747077048324075',
            '68068239036272628750825525318805297439390570305050728515552223656985804538350'
        );

        $this->assertInstanceOf($this->baseType, $this->point);
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testCreatePointFail()
    {
        $this->point = new Point(
            $this->generator,
            '940751080420169231194796483338406049382274483038030215794449747077048324075',
            '68068239036272628750825525318805297439390570305050728515552223656985804538350'
        );

        $this->assertInstanceOf($this->baseType, $this->point);
    }

    public function testToString()
    {
        $this->point = new Point(
            $this->generator,
            '94075108042016923119479678483338406049382274483038030215794449747077048324075',
            '68068239036272628750825525318805297439390570305050728515552223656985804538350'
        );

        $this->assertSame($this->point->__toString(), '[ (94075108042016923119479678483338406049382274483038030215794449747077048324075,68068239036272628750825525318805297439390570305050728515552223656985804538350) on curve(0, 7, 115792089237316195423570985008687907853269984665640564039457584007908834671663) ]');
    }

    public function testDefaultGetOrder()
    {
        $this->point = new Point(
            $this->generator,
            '94075108042016923119479678483338406049382274483038030215794449747077048324075',
            '68068239036272628750825525318805297439390570305050728515552223656985804538350'
        );

        $this->assertSame($this->point->getOrder(), '115792089237316195423570985008687907852837564279074904382605163141518161494337');
    }
}
