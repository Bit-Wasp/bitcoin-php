<?php

namespace BitWasp\Bitcoin\Tests\PaymentProtocol;

use BitWasp\Bitcoin\PaymentProtocol\RequestSigner;
use BitWasp\Bitcoin\PaymentProtocol\Protobufs\PaymentDetails;
use BitWasp\Bitcoin\PaymentProtocol\Protobufs\PaymentRequest;

class RequestSignerTest extends Bip70Test
{
    
    /**
     * @expectedException \RuntimeException
     */
    public function testWhenNotInitializedForSigning()
    {
        $signer = new RequestSigner('none');

        $request = new PaymentRequest();
        $request->setSerializedPaymentDetails((new PaymentDetails())->serialize());
        $signer->sign($request);
    }

    public function testConvenienceMethods()
    {
        $none = RequestSigner::none();
        $sha1 = RequestSigner::sha1($this->getKey(), $this->getCert());
        $sha256 = RequestSigner::sha256($this->getKey(), $this->getCert());

        $this->assertInstanceOf('\BitWasp\Bitcoin\PaymentProtocol\RequestSigner', $none);
        $this->assertInstanceOf('\BitWasp\Bitcoin\PaymentProtocol\RequestSigner', $sha1);
        $this->assertInstanceOf('\BitWasp\Bitcoin\PaymentProtocol\RequestSigner', $sha256);
    }

    public function getSignerVectors()
    {
        $key = $this->dataPath('ssl/server.key');
        $cert = $this->dataPath('ssl/server.crt');

        return [
            [RequestSigner::NONE, $key, $cert],
            [RequestSigner::SHA256, $key, $cert],
            [RequestSigner::SHA1, $key, $cert],
        ];
    }

    /**
     * @param string $method
     * @param string $keyPath
     * @param string $certPath
     * @dataProvider getSignerVectors
     */
    public function testSigner($method, $keyPath, $certPath)
    {
        $details = new PaymentDetails();
        $details->setTime(time());

        $request = new PaymentRequest();
        $request->setSerializedPaymentDetails($details->serialize());

        $signer = new RequestSigner($method, $keyPath, $certPath);
        $newRequest = $signer->sign($request);

        $this->assertEquals($method, $newRequest->getPkiType());
        $this->assertTrue($signer->verify($newRequest));
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Unsupported signature algorithm
     */
    public function testInvalidAlgo()
    {
        $paymentRequest = new PaymentRequest();
        $paymentRequest->setPkiType('nonexistant');

        $signer = new RequestSigner('none');
        $signer->verify($paymentRequest);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testFileCheckKey()
    {
        new RequestSigner(RequestSigner::SHA256, '/shouldntexist', $this->getCert());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testFileCheckCert()
    {
        new RequestSigner(RequestSigner::SHA256, $this->getKey(), '/shouldntexist');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testWithInvalidKey()
    {
        new RequestSigner(RequestSigner::SHA256, $this->getCert(), $this->getCert());
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testWithInvalidCert()
    {
        new RequestSigner(RequestSigner::SHA256, $this->getKey(), $this->getKey());
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Server does not support x.509+SHA256
     */
    public function testWhenSha256IsNotSupported()
    {
        $this->getMockWithoutSha256(RequestSigner::SHA256, $this->getKey(), $this->getCert());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testInvalidSignatureAlgorithm()
    {
        new RequestSigner('trololol', $this->getKey(), $this->getCert());
    }

    public function getMockWithoutSha256($type, $keyFile, $certFile)
    {
        $mock = $this->getMockBuilder('\BitWasp\Bitcoin\PaymentProtocol\RequestSigner')
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
