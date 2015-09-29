<?php

namespace BitWasp\Bitcoin\Tests\PaymentProtocol;

use BitWasp\Bitcoin\PaymentProtocol\PaymentRequestBuilder;
use BitWasp\Bitcoin\PaymentProtocol\PaymentRequestSigner;
use BitWasp\Bitcoin\PaymentProtocol\PaymentRequestVerifier;
use BitWasp\Bitcoin\Script\Script;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Bitcoin\Transaction\TransactionOutput;

class PaymentRequestBuilderTest extends AbstractTestCase
{
    /**
     * @var string
     */
    protected $detailsProtobuf = 'BitWasp\Bitcoin\PaymentProtocol\Protobufs\PaymentDetails';

    /**
     * @var string
     */
    protected $requestProtobuf = 'BitWasp\Bitcoin\PaymentProtocol\Protobufs\PaymentRequest';

    public function getCert()
    {
        return __DIR__ . '/../Data/ssl/server.crt';
    }
    public function getKey()
    {
        return __DIR__ . '/../Data/ssl/server.key';
    }
    public function testSetup()
    {
        $nox509 = new PaymentRequestSigner('none');
        $builder = new PaymentRequestBuilder($nox509, 'main', '1234567890');

        $details = $builder->getPaymentDetails();
        $this->assertInstanceOf($this->detailsProtobuf, $details);

        $request = $builder->getPaymentRequest();
        $this->assertInstanceOf($this->requestProtobuf, $request);
    }

    public function testMakeRequest()
    {
        $network = 'main';
        $time = '1234567890';

        $nox509 = new PaymentRequestSigner('none');
        $builder = new PaymentRequestBuilder($nox509, $network, $time);

        $amts = [50, 123, 666];
        foreach ($amts as $amt) {
            $builder->addOutput(new TransactionOutput($amt, new Script()));
        }

        $details = $builder->getPaymentDetails();
        $this->assertEquals($network, $details->getNetwork());
        $this->assertEquals($time, $details->getTime());

        $list = $details->getOutputsList();
        foreach ($list as $c => $i) {
            $this->assertEquals($amts[$c], $i->getAmount());
        }

        $this->assertEquals(3, count($list));
    }

    public function getAlgoVectors()
    {
        return [
            ['x509+sha256', OPENSSL_ALGO_SHA256],
            ['x509+sha1', OPENSSL_ALGO_SHA1],
        ];
    }

    /**
     * @dataProvider getAlgoVectors
     * @param string $pkiType
     * @param int $pkiConst
     */
    public function testSignedRequest($pkiType, $pkiConst)
    {
        $network = 'main';
        $time = '1234567890';

        $signer = new PaymentRequestSigner($pkiType, $this->getKey(), $this->getCert());
        $builder = new PaymentRequestBuilder($signer, $network, $time);

        $amts = [50, 123, 666];
        foreach ($amts as $amt) {
            $builder->addOutput(new TransactionOutput($amt, new Script()));
        }

        $request = $builder->getPaymentRequest();
        $this->assertTrue($request->hasSignature());
        $this->assertEquals($pkiType, $request->getPkiType());

        $verifier = new PaymentRequestVerifier($request);
        $this->assertTrue($verifier->verifySignature());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testFileCheckKey()
    {
        new PaymentRequestSigner('x509+sha256', '/shouldntexist', $this->getCert());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testFileCheckCert()
    {
        new PaymentRequestSigner('x509+sha256', $this->getKey(), '/shouldntexist');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testWithInvalidKey()
    {
        new PaymentRequestSigner('x509+sha256', $this->getCert(), $this->getCert());
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testWithInvalidCert()
    {
        new PaymentRequestSigner('x509+sha256', $this->getKey(), $this->getKey());
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Server does not support x.509+SHA256
     */
    public function testWhenSha256IsNotSupported()
    {
        $this->getMockWithoutSha256('x509+sha256', $this->getKey(), $this->getCert());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testInvalidSignatureAlgorithm()
    {
        new PaymentRequestSigner('trololol', $this->getKey(), $this->getCert());
    }

    public function getMockWithoutSha256($type, $keyFile, $certFile)
    {
        $mock = $this->getMockBuilder('\BitWasp\Bitcoin\PaymentProtocol\PaymentRequestSigner')
            ->setMethods([
                'supportsSha256'
            ])
            ->setConstructorArgs([
                $type, $keyFile, $certFile
            ])
            ->getMock();

        $mock->expects($this->any())
            ->method('supportsSha256')
            ->willReturn(false);

        return $mock;
    }
}
