<?php

namespace BitWasp\Bitcoin\Payments;


class PaymentRequest
{
    protected $paymentDetails;

    public function setDetails(PaymentDetails $details)
    {
        $this->paymentDetails = $details;
    }

    public function signWithKeyfile($filename)
    {

    }
}