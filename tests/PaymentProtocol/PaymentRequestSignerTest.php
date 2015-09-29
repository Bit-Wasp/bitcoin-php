<?php

namespace BitWasp\Bitcoin\Tests\PaymentProtocol;

use BitWasp\Bitcoin\PaymentProtocol\PaymentRequestSigner;
use BitWasp\Bitcoin\Tests\AbstractTestCase;

class PaymentRequestSignerTest extends AbstractTestCase
{
    /**
     * @expectedException \RuntimeException
     */
    public function testWhenNotInitializedForSigning()
    {
        $signer = new PaymentRequestSigner('none');
        $signer->signData('');
    }
}
