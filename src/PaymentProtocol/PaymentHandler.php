<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\PaymentProtocol;

use BitWasp\Bitcoin\PaymentProtocol\Protobufs\Payment;
use BitWasp\Bitcoin\PaymentProtocol\Protobufs\PaymentACK;

class PaymentHandler
{
    /**
     * @param Payment $payment
     * @param string $memo
     * @return PaymentACK
     */
    public function getPaymentAck(Payment $payment, string $memo = null): PaymentACK
    {
        $ack = new PaymentACK();
        $ack->setPayment($payment);

        if (is_string($memo)) {
            $ack->setMemo($memo);
        }

        return $ack;
    }
}
