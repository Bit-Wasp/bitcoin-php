<?php

namespace BitWasp\Bitcoin\Tests\PaymentProtocol;

use BitWasp\Bitcoin\Key\PublicKeyFactory;
use BitWasp\Bitcoin\PaymentProtocol\RequestBuilder;
use BitWasp\Bitcoin\PaymentProtocol\RequestSigner;
use BitWasp\Bitcoin\Script\Script;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Transaction\TransactionOutput;

class RequestBuilderTest extends Bip70Test
{
    
    public function testCreatesDetails()
    {
        $memo = '';
        $time = time();
        $network = '';
        $expires = '';
        $payment_url = '';
        $merchant_data = '';

        $builder = new RequestBuilder();
        $builder
            ->setMemo($memo)
            ->setTime($time)
            ->setNetwork($network)
            ->setExpires($expires)
            ->setPaymentUrl($payment_url)
            ->setMerchantData($merchant_data)
        ;

        $script = new Script();
        $builder->setOutputs([
            new TransactionOutput(1, $script),
            new TransactionOutput(12, $script)
        ]);

        $details = $builder->getPaymentDetails();
        $this->assertEquals($memo, $details->getMemo());
        $this->assertEquals($time, $details->getTime());
        $this->assertEquals($network, $details->getNetwork());
        $this->assertEquals($expires, $details->getExpires());
        $this->assertEquals($payment_url, $details->getPaymentUrl());
        $this->assertEquals($merchant_data, $details->getMerchantData());

        $this->assertEquals(1, $details->getOutputs(0)->getAmount());
        $this->assertEquals(12, $details->getOutputs(1)->getAmount());
    }

    public function testBuiltByAddress()
    {
        $builder =  new RequestBuilder();
        $builder->setTime(1);
        $address = PublicKeyFactory::fromHex('0496b538e853519c726a2c91e61ec11600ae1390813a627c66fb8be7947be63c52da7589379515d4e0a604f8141781e62294721166bf621e73a82cbf2342c858ee')->getAddress();
        $script = ScriptFactory::scriptPubKey()->payToAddress($address);

        $builder->addAddressPayment($address, 50);

        $request = $builder->getPaymentDetails();
        $output = $request->getOutputs();

        $this->assertEquals($script->getBinary(), $output[0]->getScript());
        $this->assertEquals(50, $output[0]->getAmount());
    }
    
    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Time not set on PaymentDetails
     */
    public function testRequiresTime()
    {
        (new RequestBuilder())->getPaymentDetails();
    }
    
    public function testSignerHasEffect()
    {
        $builder = new RequestBuilder();
        $builder->setTime(1);
        $builder->setSigner(RequestSigner::none());
        $requestNone = $builder->getPaymentRequest();
        $builder->setSigner(RequestSigner::sha1($this->getKey(), $this->getCert()));
        $requestSha1 = $builder->getPaymentRequest();

        $this->assertEquals(RequestSigner::NONE, $requestNone->getPkiType());
        $this->assertEquals('', $requestNone->getSignature());

        $this->assertEquals(RequestSigner::SHA1, $requestSha1->getPkiType());
        $this->assertNotEquals('', $requestSha1->getSignature());

    }
}
