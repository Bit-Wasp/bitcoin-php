<?php

namespace BitWasp\Bitcoin\Tests\Signature;

use BitWasp\Bitcoin\Crypto\EcAdapter\Adapter\EcAdapterInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\Impl\PhpEcc\Signature\Signature;
use BitWasp\Bitcoin\Signature\SignatureFactory;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Bitcoin\Crypto\EcAdapter\Impl\PhpEcc\Adapter\EcAdapter as PhpeccAdapter;

/**
 * Class SignatureTest
 * @package Bitcoin
 */
class SignatureTest extends AbstractTestCase
{
    /**
     * @var string
     */
    protected $sigType = 'BitWasp\Bitcoin\Crypto\EcAdapter\Signature\SignatureInterface';

    public function getPhpEcc()
    {
        return new PhpeccAdapter($this->safeMath(), $this->safeGenerator());
    }

    public function testCreatesSignature()
    {
        $sig = new Signature($this->getPhpEcc(), '15148391597642804072346119047125209977057190235171731969261106466169304622925', '29241524176690745465970782157695275252863180202254265092780741319779241938696');
        $this->assertInstanceOf($this->sigType, $sig);
    }

    public function testSerialize()
    {
        $sig = new Signature($this->getPhpEcc(), '56860522993476239843569407076292679822350064328987049204205911586688428093823', '75328468267675219166053001951181042681597800329127462438170420074748074627387');
        $this->assertInstanceOf($this->sigType, $sig);
        $this->assertEquals('304502207db5ea602fe2e9f8e70bfc68b7f468d68910d2ff4ac50294fc80109e254f317f022100a68a66f23406fdfd93025c28ffef4e79260283335ce39a4e8d0b52c5ee41913b', $sig->getHex());
    }

    public function testGetRS()
    {
        $sig = new Signature($this->getPhpEcc(), '15148391597642804072346119047125209977057190235171731969261106466169304622925', '29241524176690745465970782157695275252863180202254265092780741319779241938696');
        $this->assertSame($sig->getR(), '15148391597642804072346119047125209977057190235171731969261106466169304622925');
        $this->assertSame($sig->getS(), '29241524176690745465970782157695275252863180202254265092780741319779241938696');
    }

    /**
     * @dataProvider getEcAdapters
     * @param EcAdapterInterface $ecAdapter
     */
    public function testFromHex(EcAdapterInterface $ecAdapter)
    {

        $hex = '304502207db5ea602fe2e9f8e70bfc68b7f468d68910d2ff4ac50294fc80109e254f317f022100a68a66f23406fdfd93025c28ffef4e79260283335ce39a4e8d0b52c5ee41913b';
        $sig = SignatureFactory::fromHex($hex, $ecAdapter);

        $this->assertInstanceOf($this->sigType, $sig);
        $this->assertEquals('56860522993476239843569407076292679822350064328987049204205911586688428093823', $sig->getR());
        $this->assertEquals('75328468267675219166053001951181042681597800329127462438170420074748074627387', $sig->getS());

    }
}
