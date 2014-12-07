<?php

namespace Bitcoin\Tests\Signature;

use Bitcoin\Signature\Signature;

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

    protected $sigType;

    public function __construct()
    {
        $this->sigType = 'Bitcoin\Signature\Signature';
    }

    public function setUp()
    {
        $this->sig = null;
    }

    public function testCreatesSignature()
    {
        $this->sig = new Signature(15148391597642804072346119047125209977057190235171731969261106466169304622925, 29241524176690745465970782157695275252863180202254265092780741319779241938696);
        $this->assertInstanceOf($this->sigType, $this->sig);
    }

    public function testGetR()
    {
        $this->sig = new Signature(15148391597642804072346119047125209977057190235171731969261106466169304622925, 29241524176690745465970782157695275252863180202254265092780741319779241938696);
        $this->assertSame($this->sig->getR(), 15148391597642804072346119047125209977057190235171731969261106466169304622925);
    }

    public function testGetS()
    {
        $this->sig = new Signature(15148391597642804072346119047125209977057190235171731969261106466169304622925, 29241524176690745465970782157695275252863180202254265092780741319779241938696);
        $this->assertSame($this->sig->getS(), 29241524176690745465970782157695275252863180202254265092780741319779241938696);
    }
}
