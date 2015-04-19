<?php

namespace BitWasp\Bitcoin\Tests\Signature;

use BitWasp\Bitcoin\Exceptions\SignatureNotCanonical;
use BitWasp\Bitcoin\Signature\Signature;
use BitWasp\Buffertools\Buffer;
use BitWasp\Bitcoin\Signature\SignatureFactory;

/**
 * Class SignatureTest
 * @package Bitcoin
 */
class SignatureTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Signature
     */
    protected $sig;

    /**
     * @var string
     */
    protected $sigType;

    /**
     *
     */
    public function __construct()
    {
        $this->sigType = 'BitWasp\Bitcoin\Signature\Signature';
    }

    public function setUp()
    {
        $this->sig = null;
    }

    public function testCreatesSignature()
    {
        $this->sig = new Signature('15148391597642804072346119047125209977057190235171731969261106466169304622925', '29241524176690745465970782157695275252863180202254265092780741319779241938696');
        $this->assertInstanceOf($this->sigType, $this->sig);
    }

    public function testSerialize()
    {
        $this->sig = new Signature('56860522993476239843569407076292679822350064328987049204205911586688428093823', '75328468267675219166053001951181042681597800329127462438170420074748074627387');
        $this->assertInstanceOf($this->sigType, $this->sig);
        $this->assertEquals('304502207db5ea602fe2e9f8e70bfc68b7f468d68910d2ff4ac50294fc80109e254f317f022100a68a66f23406fdfd93025c28ffef4e79260283335ce39a4e8d0b52c5ee41913b', $this->sig->getHex());
    }

    public function testGetR()
    {
        $this->sig = new Signature('15148391597642804072346119047125209977057190235171731969261106466169304622925', '29241524176690745465970782157695275252863180202254265092780741319779241938696');
        $this->assertSame($this->sig->getR(), '15148391597642804072346119047125209977057190235171731969261106466169304622925');
    }

    public function testGetS()
    {
        $this->sig = new Signature('15148391597642804072346119047125209977057190235171731969261106466169304622925', '29241524176690745465970782157695275252863180202254265092780741319779241938696');
        $this->assertSame($this->sig->getS(), '29241524176690745465970782157695275252863180202254265092780741319779241938696');
    }

    public function testFromHex()
    {
        $hex = '304502207db5ea602fe2e9f8e70bfc68b7f468d68910d2ff4ac50294fc80109e254f317f022100a68a66f23406fdfd93025c28ffef4e79260283335ce39a4e8d0b52c5ee41913b01';
        $this->sig = SignatureFactory::fromHex($hex);

        $this->assertInstanceOf('BitWasp\Bitcoin\Signature\Signature', $this->sig);
        $this->assertEquals('56860522993476239843569407076292679822350064328987049204205911586688428093823', $this->sig->getR());
        $this->assertEquals('75328468267675219166053001951181042681597800329127462438170420074748074627387', $this->sig->getS());
    }
}
