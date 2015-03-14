<?php

namespace Afk11\Bitcoin\Tests\Signature;

use Afk11\Bitcoin\Exceptions\SignatureNotCanonical;use Afk11\Bitcoin\Signature\Signature;
use Afk11\Bitcoin\Buffer;
use Afk11\Bitcoin\Signature\SignatureFactory;

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
        $this->sigType = 'Afk11\Bitcoin\Signature\Signature';
    }

    public function setUp()
    {
        $this->sig = null;
    }

    public function testSigCanonical()
    {
        $f    = file_get_contents(__DIR__ . '/../Data/sig_canonical.json');
        $json = json_decode($f);
        foreach ($json->test as $test) {
            $sigBuf = Buffer::hex($test);
            $this->assertTrue(Signature::isDERSignature($sigBuf));
        }
    }

    public function testSigNonCanonical()
    {
        $f    = file_get_contents(__DIR__ . '/../Data/sig_noncanonical.json');
        $json = json_decode($f);
        foreach ($json->test as $c => $test) {
            try {
                $sigBuf = Buffer::hex($test[1]);
                Signature::isDERSignature($sigBuf);
                throw new \Exception("Failed testing for case: ". $test[0]);
            } catch (SignatureNotCanonical $e) {
                $this->assertTrue(true);
            }
        }
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
        $this->assertEquals('304502207db5ea602fe2e9f8e70bfc68b7f468d68910d2ff4ac50294fc80109e254f317f022100a68a66f23406fdfd93025c28ffef4e79260283335ce39a4e8d0b52c5ee41913b01', $this->sig->getBuffer()->serialize('hex'));
    }

    public function testDefaultSighashType()
    {
        $this->sig = new Signature('15148391597642804072346119047125209977057190235171731969261106466169304622925', '29241524176690745465970782157695275252863180202254265092780741319779241938696');
        $this->AssertEquals(1, $this->sig->getSighashType());
    }

    public function testSetSighashType()
    {
        $this->sig = new Signature('15148391597642804072346119047125209977057190235171731969261106466169304622925', '29241524176690745465970782157695275252863180202254265092780741319779241938696', 0x81);
        $this->assertEquals(0x81, $this->sig->getSighashType());
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

    public function test2()
    {
        $s = '304402203bc90d68b698347ea1f4b51446a0725d177debe99736df2718a9bc82275a17c402200d250e0d75c1123d179d029680bd7e2a08a4917a7e3beff25b6dbdeadbe1598901';

        $this->assertTrue(Signature::isDERSignature(Buffer::hex($s)));
        $sig      = SignatureFactory::fromHex($s);
        $this->assertTrue(Signature::isDERSignature($sig->getBuffer()));
        $this->assertSame($s, $sig->getBuffer()->serialize('hex'));
    }

    public function testSignaturesConsistent()
    {
        $f    = file_get_contents(__DIR__ . '/../Data/signatures_blockchain.json');
        $json = json_decode($f);
        foreach ($json->test as $c => $test) {
            $sig  = SignatureFactory::fromHex($test);
            $sd   = $sig->getBuffer()->serialize('hex');
            $this->assertSame($test, $sd);
        }
    }

    public function testFromHex()
    {
        $hex = '304502207db5ea602fe2e9f8e70bfc68b7f468d68910d2ff4ac50294fc80109e254f317f022100a68a66f23406fdfd93025c28ffef4e79260283335ce39a4e8d0b52c5ee41913b01';
        $this->sig = SignatureFactory::fromHex($hex);

        $this->assertInstanceOf('Afk11\Bitcoin\Signature\Signature', $this->sig);
        $this->assertEquals('56860522993476239843569407076292679822350064328987049204205911586688428093823', $this->sig->getR());
        $this->assertEquals('75328468267675219166053001951181042681597800329127462438170420074748074627387', $this->sig->getS());
    }
}
