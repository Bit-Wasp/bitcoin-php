<?php

namespace BitWasp\Bitcoin\PaymentProtocol;

use BitWasp\Bitcoin\Address\AddressInterface;
use BitWasp\Buffertools\Buffer;
use BitWasp\Bitcoin\PaymentProtocol\Protobufs\Output as OutputBuf;
use BitWasp\Bitcoin\PaymentProtocol\Protobufs\PaymentRequest as PaymentRequestBuf;
use BitWasp\Bitcoin\PaymentProtocol\Protobufs\PaymentDetails as PaymentDetailsBuf;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Transaction\TransactionOutput;
use BitWasp\Bitcoin\Transaction\TransactionOutputInterface;

class PaymentRequestBuilder
{
    /**
     * @var int
     */
    private $outputCount = 0;

    /**
     * @var PaymentRequestBuf
     */
    private $request;

    /**
     * @var PaymentDetailsBuf
     */
    private $details;

    /**
     * @var PaymentRequestSigner
     */
    private $signer;

    /**
     * @param PaymentRequestSigner $signer
     * @param string $network
     * @param int $time
     */
    public function __construct(PaymentRequestSigner $signer, $network, $time)
    {
        if (!in_array($network, ['main','test'])) {
            throw new \InvalidArgumentException('Network must be main or test');
        }

        $this->details = new PaymentDetailsBuf();
        $this->details
            ->setNetwork($network)
            ->setTime($time);

        $this->request = new PaymentRequestBuf();
        $this->signer = $signer;
    }

    /**
     * @param $string
     * @return $this
     */
    public function parse($string)
    {
        $this->request->parse($string);
        $this->details->parse($this->request->getSerializedPaymentDetails());
        $this->outputCount = count($this->details->getOutputsList());
        return $this;
    }

    /**
     * @return PaymentDetailsBuf
     */
    public function getPaymentDetails()
    {
        return $this->details;
    }

    /**
     * @return PaymentRequestBuf
     * @throws \Exception
     */
    public function getPaymentRequest()
    {
        // Serialize the payment details, and apply a signature based on instance of PaymentRequestSigner
        $this->request->setSerializedPaymentDetails($this->details->serialize());
        if (!$this->request->hasSignature()) {
            $this->request = $this->signer->apply($this->request);
        }

        return $this->request;
    }

    /**
     * @param TransactionOutputInterface $txOutput
     * @return OutputBuf
     */
    private function outputToBuf(TransactionOutputInterface $txOutput)
    {
        $output = new OutputBuf();
        $output->setScript($txOutput->getScript()->getBuffer()->getBinary());
        $output->setAmount($txOutput->getValue());
        return $output;
    }

    /**
     * @param OutputBuf $outBuf
     * @return TransactionOutput
     */
    private function bufToOutput(OutputBuf $outBuf)
    {
        $script = ScriptFactory::create(new Buffer($outBuf->getScript()));
        $output = new TransactionOutput($outBuf->getAmount(), $script);
        return $output;
    }

    /**
     * @param TransactionOutputInterface $txOutput
     * @return $this
     */
    public function addOutput(TransactionOutputInterface $txOutput)
    {
        $output = $this->outputToBuf($txOutput);
        $this->details->addOutputs($output);
        return $this;
    }

    /**
     * @param AddressInterface $address
     * @param $value
     * @return PaymentRequestBuilder
     */
    public function addAddressPayment(AddressInterface $address, $value)
    {
        $script = ScriptFactory::scriptPubKey()->payToAddress($address);
        $output = new TransactionOutput($value, $script);
        return $this->addOutput($output);
    }

    /**
     * @param $index
     * @return OutputBuf
     */
    public function getOutput($index)
    {
        if ($index < 0 || $index > $this->outputCount) {
            throw new \InvalidArgumentException('Output not found at this index');
        }

        return $this->bufToOutput($this->details->getOutputs($index));
    }

    /**
     * @return Protobufs\Output[]
     */
    public function getOutputs()
    {
        return array_map(
            function (OutputBuf $outBuf) {
                $this->bufToOutput($outBuf);
            },
            $this->details->getOutputsList()
        );
    }

    /**
     * Serialize the payment request and make the
     */
    public function send()
    {
        $data = $this->getPaymentRequest()->serialize();
        $filename = "payment" . (string)time() . ".bitcoinpaymentrequest";

        header('Content-Type: application/bitcoin-paymentrequest');
        header('Content-Disposition: inline; filename=' . $filename);
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Content-Length: ' . (string)strlen($data));
        echo $data;

        return $this;
    }
}
