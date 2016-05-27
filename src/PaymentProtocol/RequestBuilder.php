<?php

namespace BitWasp\Bitcoin\PaymentProtocol;

use BitWasp\Bitcoin\Address\AddressInterface;
use BitWasp\Bitcoin\PaymentProtocol\Protobufs\Output;
use BitWasp\Bitcoin\PaymentProtocol\Protobufs\PaymentDetails;
use BitWasp\Bitcoin\PaymentProtocol\Protobufs\PaymentRequest;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Transaction\TransactionOutput;
use BitWasp\Bitcoin\Transaction\TransactionOutputInterface;

class RequestBuilder
{
    /**
     * Optional
     * @var string|null
     */
    private $network;

    /**
     * Repeated
     * @var TransactionOutputInterface[]
     */
    private $outputs = [];

    /**
     * Required
     * @var int
     */
    private $time;

    /**
     * Optional
     * @var int|null
     */
    private $expires;

    /**
     * Optional
     * @var string
     */
    private $memo;

    /**
     * Optional
     * @var string
     */
    private $payment_url;

    /**
     * Optional
     * @var string
     */
    private $merchant_data;

    /**
     * @var RequestSigner
     */
    private $signer;

    /**
     * @param string $memo
     * @return $this
     */
    public function setMemo($memo)
    {
        $this->memo = $memo;
        return $this;
    }

    /**
     * @param string $network
     * @return $this
     */
    public function setNetwork($network)
    {
        $this->network = $network;
        return $this;
    }

    /**
     * @param array $outputs
     * @return $this
     */
    public function setOutputs(array $outputs)
    {
        $this->outputs = [];
        foreach ($outputs as $output) {
            $this->addOutput($output);
        }

        return $this;
    }

    /**
     * @param TransactionOutputInterface $output
     * @return $this
     */
    public function addOutput(TransactionOutputInterface $output)
    {
        $this->outputs[] = $output;
        return $this;
    }

    /**
     * @param AddressInterface $address
     * @param $value
     * @return $this
     */
    public function addAddressPayment(AddressInterface $address, $value)
    {
        $script = ScriptFactory::scriptPubKey()->payToAddress($address);
        $output = new TransactionOutput($value, $script);
        return $this->addOutput($output);
    }

    /**
     * @param int $time
     * @return RequestBuilder
     */
    public function setTime($time)
    {
        $this->time = $time;
        return $this;
    }

    /**
     * @param int $expires
     * @return RequestBuilder
     */
    public function setExpires($expires)
    {
        $this->expires = $expires;
        return $this;
    }

    /**
     * @param string $payment_url
     * @return RequestBuilder
     */
    public function setPaymentUrl($payment_url)
    {
        $this->payment_url = $payment_url;
        return $this;
    }

    /**
     * @param string $merchant_data
     * @return RequestBuilder
     */
    public function setMerchantData($merchant_data)
    {
        $this->merchant_data = $merchant_data;
        return $this;
    }

    /**
     * @param RequestSigner $signer
     * @return $this
     */
    public function setSigner(RequestSigner $signer)
    {
        $this->signer = $signer;
        return $this;
    }

    /**
     * @return PaymentDetails
     */
    public function getPaymentDetails()
    {
        if (is_null($this->time)) {
            throw new \RuntimeException('Time not set on PaymentDetails');
        }

        $details = new PaymentDetails();
        if (!is_null($this->network)) {
            $details->setNetwork($this->network);
        }

        $c = 0;
        array_map(function (TransactionOutputInterface $output) use ($details, &$c) {
            $details->setOutputs(
                (new Output())
                    ->setAmount($output->getValue())
                    ->setScript($output->getScript()->getBinary()),
                $c++
            );
        }, $this->outputs);

        $details->setTime($this->time);
        if (!is_null($this->expires)) {
            $details->setExpires($this->expires);
        }

        if (!is_null($this->memo)) {
            $details->setMemo($this->memo);
        }

        if (!is_null($this->payment_url)) {
            $details->setPaymentUrl($this->payment_url);
        }

        if (!is_null($this->merchant_data)) {
            $details->setMerchantData($this->merchant_data);
        }

        return $details;
    }

    /**
     * @return PaymentRequest
     */
    public function getPaymentRequest()
    {
        // Serialize the payment details, and apply a signature based on instance of PaymentRequestSigner
        $request = new PaymentRequest();
        $request->setSerializedPaymentDetails($this->getPaymentDetails()->serialize());

        if ($this->signer instanceof RequestSigner) {
            $request = $this->signer->sign($request);
        }

        return $request;
    }
}
