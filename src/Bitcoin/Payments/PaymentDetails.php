<?php

namespace BitWasp\Bitcoin\Payments;


use BitWasp\Bitcoin\Transaction\TransactionOutputInterface;

class PaymentDetails
{
    protected $network;
    protected $time;
    protected $expires;
    protected $memo;
    protected $merchantData;
    protected $paymentUrl;
    protected $outputs = [];

    public function __construct($network)
    {
        if (!in_array($network, ['main','test'])) {
            throw new \InvalidArgumentException('Network must be main or test');
        }
        $this->network = $network;
    }

    public function setExpires($expiration)
    {
        $this->expires = $expiration;
        return $this;
    }

    public function setMemo($memo)
    {
        $this->memo = $memo;
        return $this;
    }

    public function setPaymentUrl($url)
    {
        $this->paymentUrl = $url;
        return $this;
    }

    public function setMerchantData($string)
    {
        $this->merchantData = $string;
        return $this;
    }

    public function setTime($time)
    {
        $this->time = $time;
        return $this;
    }

    public function addOutput(TransactionOutputInterface $output)
    {

    }
}