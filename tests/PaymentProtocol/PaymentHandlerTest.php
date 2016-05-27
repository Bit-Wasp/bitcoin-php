<?php

namespace BitWasp\Bitcoin\Tests\PaymentProtocol;

use BitWasp\Bitcoin\PaymentProtocol\PaymentHandler;
use BitWasp\Bitcoin\PaymentProtocol\Protobufs\Payment;

class PaymentHandlerTest extends Bip70Test
{
    public function testCreatesAck()
    {
        $memo = 'thanks for paying';
        $payment = new Payment();
        $handler = new PaymentHandler();

        $ack = $handler->getPaymentAck($payment, $memo);
        $this->assertTrue($ack->hasPayment());
        $this->assertTrue($ack->hasMemo());
        $this->assertEquals($payment, $ack->getPayment());
        $this->assertEquals($memo, $ack->getMemo());

        $ack = $handler->getPaymentAck($payment);
        $this->assertTrue($ack->hasPayment());
        $this->assertFalse($ack->hasMemo());
        $this->assertEquals($payment, $ack->getPayment());
    }
}
