<?php

namespace BitWasp\Bitcoin\Tests\PaymentProtocol;

use BitWasp\Bitcoin\PaymentProtocol\PaymentRequestBuilder;
use BitWasp\Bitcoin\PaymentProtocol\PaymentRequestSigner;
use BitWasp\Bitcoin\PaymentProtocol\PaymentRequestVerifier;
use BitWasp\Bitcoin\Tests\AbstractTestCase;

class PaymentRequestVerifierTest extends AbstractTestCase
{
    public function testWithNoPki()
    {
        $builder = new PaymentRequestBuilder(new PaymentRequestSigner('none'), 'main', '1');
        $request = $builder->getPaymentRequest();
        $verifier = new PaymentRequestVerifier($request);
        $this->assertTrue($verifier->verifySignature());

    }
}
