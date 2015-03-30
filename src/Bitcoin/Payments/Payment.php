<?php

namespace BitWasp\Bitcoin\Payments;


class Payment
{
    protected $merchantData;

    public function setMerchantData($data)
    {
        $this->merchantData = $data;
        return $this;
    }
}