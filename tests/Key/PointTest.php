<?php

namespace BitWasp\Bitcoin\Tests\Key;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Key\Point;
use BitWasp\Bitcoin\Tests\AbstractTestCase;

class PointTest extends AbstractTestCase
{
    /**
     * @var string
     */
    protected $pointType = 'BitWasp\Bitcoin\Key\Point';

    /**
     * @var \BitWasp\Bitcoin\Math\Math
     */
    protected $math;

    /**
     * @var \Mdanter\Ecc\Primitives\GeneratorPoint
     */
    protected $generator;

    public function __construct()
    {
        $this->math = Bitcoin::getMath();
        $this->generator = Bitcoin::getGenerator();
    }

    public function testCreatePoint()
    {
        $point = new Point(
            $this->math,
            $this->generator,
            '94075108042016923119479678483338406049382274483038030215794449747077048324075',
            '68068239036272628750825525318805297439390570305050728515552223656985804538350'
        );

        $this->assertInstanceOf($this->pointType, $point);
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testCreatePointFail()
    {
        new Point(
            $this->math,
            $this->generator,
            '940751080420169231194796483338406049382274483038030215794449747077048324075',
            '68068239036272628750825525318805297439390570305050728515552223656985804538350'
        );
    }

    public function testDefaultGetOrder()
    {
        $point = new Point(
            $this->math,
            $this->generator,
            '94075108042016923119479678483338406049382274483038030215794449747077048324075',
            '68068239036272628750825525318805297439390570305050728515552223656985804538350'
        );

        $this->assertSame('115792089237316195423570985008687907852837564279074904382605163141518161494337', $point->getOrder());
    }
}
