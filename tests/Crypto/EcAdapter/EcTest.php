<?php

namespace BitWasp\Bitcoin\Tests\Crypto\EcAdapter;

use BitWasp\Bitcoin\Crypto\EcAdapter\Adapter\EcAdapterInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\PhpEcc;
use BitWasp\Bitcoin\Crypto\EcAdapter\Impl\PhpEcc\Key\PrivateKey;
use BitWasp\Bitcoin\Key\PrivateKeyFactory;
use BitWasp\Bitcoin\Crypto\EcAdapter\Key\PrivateKeyInterface;
use BitWasp\Bitcoin\Math\Math;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Buffertools\Buffer;
use Mdanter\Ecc\EccFactory;

class EcTest extends AbstractTestCase
{

    /**
     * @param EcAdapterInterface $ecAdapterInterface
     * @return PrivateKey
     */
    public function getFirstPrivateKey(EcAdapterInterface $ecAdapterInterface)
    {
        return new PrivateKey($ecAdapterInterface, 1);
    }

    /**
     * @param PrivateKeyInterface $private
     * @param $add
     * @param EcAdapterInterface $ec
     * @return int|string
     */
    public function addModN(PrivateKeyInterface $private, $add, EcAdapterInterface $ec)
    {
        $math = $ec->getMath();
        return $math->mod($math->add($private->getSecretMultiplier(), $add), $ec->getGenerator()->getOrder());
    }

    /**
     * @param PrivateKeyInterface $private
     * @param $add
     * @param EcAdapterInterface $ec
     * @return string
     */
    public function mulModN(PrivateKeyInterface $private, $add, EcAdapterInterface $ec)
    {
        $math = $ec->getMath();
        return $math->mod($math->mul($private->getSecretMultiplier(), $add), $ec->getGenerator()->getOrder());
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Failed to find valid recovery factor
     */
    public function testCalcPubkeyRecidFail()
    {
        $math = new Math();
        $g = EccFactory::getSecgCurves($math)->generator256k1();

        $phpecc = new PhpEcc($math, $g);
        $private = PrivateKeyFactory::fromInt(1);
        $phpecc->calcPubKeyRecoveryParam(1, 1, Buffer::hex('4141414141414141414141414141414141414141414141414141414141414141'), $private->getPublicKey());
    }

    /**
     * @dataProvider getEcAdapters
     */
    public function testAdd(EcAdapterInterface $ec)
    {
        $private = $this->getFirstPrivateKey($ec);
        $public = $private->getPublicKey();
        $tweak = 1;

        $expected = new PrivateKey($ec, $this->addModN($private, $tweak, $ec));
        $expectedPub = $expected->getPublicKey();
        // Check addModN works just the same
        $this->assertEquals('2', $expected->getSecretMultiplier());

        // k + k % n
        $new = $private->tweakAdd($tweak);
        $this->assertEquals('2', $new->getSecretMultiplier());

        // (k + k % n) * G
        // Check our publickey matches that of expectedPub
        $tweaked = $new->getPublicKey();
        $this->assertEquals($expectedPub->getPoint()->getX(), $tweaked->getPoint()->getX());
        $this->assertEquals($expectedPub->getPoint()->getY(), $tweaked->getPoint()->getY());

        // (k+k%n)*G  === k*G +(tweak*G) (since tweak == k)
        $tweaked = $public->tweakAdd($tweak);
        $this->assertEquals($expectedPub->getPoint()->getX(), $tweaked->getPoint()->getX());
        $this->assertEquals($expectedPub->getPoint()->getY(), $tweaked->getPoint()->getY());
    }

    /**
     * @dataProvider getEcAdapters
     * @param EcAdapterInterface $ec
     */
    public function testMul(EcAdapterInterface $ec)
    {
        $private = $this->getFirstPrivateKey($ec);
        $public = $private->getPublicKey();
        $tweak = 4;

        $expected = new PrivateKey($ec, $this->mulModN($private, $tweak, $ec));
        $expectedPub = $expected->getPublicKey();
        // Check addModN works just the same
        $this->assertEquals('4', $expected->getSecretMultiplier());

        $new = $private->tweakMul($tweak);
        $this->assertEquals('4', $new->getSecretMultiplier());

        $tweaked = $new->getPublicKey();
        $this->assertEquals($expectedPub->getPoint()->getX(), $tweaked->getPoint()->getX());
        $this->assertEquals($expectedPub->getPoint()->getY(), $tweaked->getPoint()->getY());

        $tweaked = $public->tweakMul($tweak);
        $this->assertEquals($expectedPub->getPoint()->getX(), $tweaked->getPoint()->getX());
        $this->assertEquals($expectedPub->getPoint()->getY(), $tweaked->getPoint()->getY());
    }

    /**
     * @dataProvider getEcAdapters
     * @param EcAdapterInterface $ec
     */
    public function testSign(EcAdapterInterface $ec)
    {
        $private = $this->getFirstPrivateKey($ec);
        $messageHash = Buffer::hex('0100000000000000000000000000000000000000000000000000000000000000', 32);

        $signature = $ec->sign($messageHash, $private);
        $this->assertTrue($ec->verify($messageHash, $private->getPublicKey(), $signature));
    }

    /**
     * @dataProvider getEcAdapters
     * @param EcAdapterInterface $ec
     */
    public function testSignCompact(EcAdapterInterface $ec)
    {
        $private = $this->getFirstPrivateKey($ec);
        $messageHash = Buffer::hex('0100000000000000000000000000000000000000000000000000000000000000', 32);

        $compact = $ec->signCompact($messageHash, $private);
        $publicKey = $ec->recoverCompact($messageHash, $compact);
        $this->assertEquals($private->isCompressed(), $publicKey->isCompressed());
        $this->assertEquals($private->getPublicKey()->getPoint()->getX(), $publicKey->getPoint()->getX());
        $this->assertEquals($private->getPublicKey()->getPoint()->getY(), $publicKey->getPoint()->getY());
    }

    public function testPhpeccFailPrivateToPublic()
    {
        $math = new Math();
        $g = EccFactory::getSecgCurves($math)->generator256k1();

        $phpecc = new PhpEcc($math, $g);

        $mock = $this->getMock(
            'BitWasp\Bitcoin\Key\PrivateKeyInterface',
            [
                'getSecretMultiplier', 'toWif', 'getPublicKey',
                'isCompressed', 'getPubKeyHash', 'isPrivate',
                'getAddress', 'tweakMul', 'tweakAdd',
                // serializable
                'getInt', 'getBinary', 'getHex', 'getBuffer'
            ],
            [$phpecc]
        );

        $mock->expects($this->atLeastOnce())
            ->method('getSecretMultiplier')
            ->willReturn($math->baseConvert('FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFEBAAEDCE6AF48A03BBFD25E8CD0364141', 16, 10));

        $mock->expects($this->atLeastOnce())
            ->method('isCompressed')
            ->willReturn(false);

        $phpecc->privateToPublic($mock);
    }

    /**
     * @dataProvider getEcAdapters
     * @param EcAdapterInterface $ec
     */
    public function testValidatePrivateKey(EcAdapterInterface $ec)
    {
        $valid = [
            'e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855',
            'FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFEBAAEDCE6AF48A03BBFD25E8CD0364140',
            '0000000000000000000000000000000000000000000000000000000000000001',
        ];

        array_map(
            function ($value) use ($ec) {
                $this->assertTrue($ec->validatePrivateKey(Buffer::hex($value)));
            },
            $valid
        );

        $invalid = [
            'FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFEBAAEDCE6AF48A03BBFD25E8CD0364141'
        ];

        array_map(
            function ($value) use ($ec) {
                $this->assertFalse($ec->validatePrivateKey(Buffer::hex($value)));
            },
            $invalid
        );
    }

    /**
     * @dataProvider getEcAdapters
     * @param EcAdapterInterface $ec
     */
    public function testValidatePublicKey(EcAdapterInterface $ec)
    {
        $valid = [
            '04a34b99f22c790c4e36b2b3c2c35a36db06226e41c692fc82b8b56ac1c540c5bd5b8dec5235a0fa8722476c7709c02559e3aa73aa03918ba2d492eea75abea235',
            '03a34b99f22c790c4e36b2b3c2c35a36db06226e41c692fc82b8b56ac1c540c5bd'
        ];

        array_map(
            function ($value) use ($ec) {
                $this->assertTrue($ec->validatePublicKey(Buffer::hex($value)));
            },
            $valid
        );

        $invalid = [
            '040101',
            '04a34bd9f22c790c4e36b2b3c2c35a36db06226e41c692fc82b8b56ac1c540c5bd5b8dec5235a0fa8722476c7709c02559e3aa73aa03918ba2d492eea75abea255'
        ];

        array_map(
            function ($value) use ($ec) {
                $this->assertFalse($ec->validatePublicKey(Buffer::hex($value)));
            },
            $invalid
        );
    }
}
