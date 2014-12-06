<?php
/**
 * Created by PhpStorm.
 * User: thomas
 * Date: 27/11/14
 * Time: 04:58
 */

namespace Bitcoin;

class PointTest extends \PHPUnit_Framework_TestCase {

    protected $point;

    public function setUp()
    {
        $this->point = null;
    }

    public function testCreatePoint()
    {
        $this->point = new Point(
            '94075108042016923119479678483338406049382274483038030215794449747077048324075',
            '68068239036272628750825525318805297439390570305050728515552223656985804538350'
        );

        $this->assertInstanceOf('Bitcoin\Point', $this->point);
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testCreatePointFail()
    {
        $this->point = new Point(
            '940751080420169231194796483338406049382274483038030215794449747077048324075',
            '68068239036272628750825525318805297439390570305050728515552223656985804538350'
        );

        $this->assertInstanceOf('Bitcoin\Point', $this->point);
    }

    public function testToString()
    {
        $this->point = new Point(
            '94075108042016923119479678483338406049382274483038030215794449747077048324075',
            '68068239036272628750825525318805297439390570305050728515552223656985804538350'
        );

        $this->assertSame($this->point->__toString(), '(94075108042016923119479678483338406049382274483038030215794449747077048324075,68068239036272628750825525318805297439390570305050728515552223656985804538350)');
    }

    public function testDefaultGetOrder()
    {
        $this->point = new Point(
            '94075108042016923119479678483338406049382274483038030215794449747077048324075',
            '68068239036272628750825525318805297439390570305050728515552223656985804538350'
        );

        $this->assertSame($this->point->getOrder(), '115792089237316195423570985008687907852837564279074904382605163141518161494337');
    }
} 