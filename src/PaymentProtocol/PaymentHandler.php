<?php

namespace BitWasp\Bitcoin\PaymentProtocol;

use BitWasp\Bitcoin\PaymentProtocol\Protobufs\PaymentRequest as PaymentRequestBuf;
use BitWasp\Bitcoin\PaymentProtocol\Protobufs\PaymentDetails as PaymentDetailsBuf;
use BitWasp\Bitcoin\PaymentProtocol\Protobufs\Payment as PaymentBuf;
use BitWasp\Bitcoin\PaymentProtocol\Protobufs\PaymentACK as PaymentACKBuf;
use BitWasp\Bitcoin\Collection\Transaction\TransactionCollection;
use BitWasp\Bitcoin\Transaction\TransactionFactory;

class PaymentHandler
{
    /**
     * @var PaymentBuf
     */
    protected $payment;

    /**
     * @param string $response
     */
    public function __construct($response)
    {
        $this->payment = new PaymentBuf();
        $this->payment->parse($response);
    }

    /**
     * @return PaymentBuf
     */
    public function getPayment()
    {
        return $this->payment;
    }

    /**
     * @return TransactionCollection
     */
    public function getTransactions()
    {
        return new TransactionCollection(
            array_map(
                function ($binTx) {
                    return TransactionFactory::fromHex(bin2hex($binTx));
                },
                $this->payment->getTransactionsList()
            )
        );
    }

    /**
     * @param PaymentRequestBuf $request
     * @return bool
     */
    public function checkAgainstRequest(PaymentRequestBuf $request)
    {
        $details = new PaymentDetailsBuf();
        $details->parse($request->getSerializedPaymentDetails());
        $outputs = $details->getOutputsList();
        $requirements = [];
        foreach ($outputs as $out) {
            if (array_key_exists($out->getScript(), $requirements)) {
                $requirements[$out->getScript()] += $out->getAmount();
            } else {
                $requirements[$out->getScript()] = $out->getAmount();
            }
        }

        $parsed = [];

        // Check that regardless of the other outputs, that each specific output was paid.
        foreach ($this->getTransactions() as $tx) {
            foreach ($tx->getOutputs() as $output) {
                $scriptBin = $output->getScript()->getBinary();
                if (array_key_exists($scriptBin, $parsed)) {
                    $parsed[$scriptBin] += $output->getValue();
                } else {
                    $parsed[$scriptBin] = $output->getValue();
                }
            }
        }

        foreach ($requirements as $script => $value) {
            if (!array_key_exists($script, $parsed)) {
                return false;
            }
            if ($parsed[$script] < $value) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param string|null $response
     * @return PaymentACKBuf
     */
    public function getAck($response = null)
    {
        $paymentAck = new PaymentACKBuf();
        $paymentAck
            ->setPayment($this->payment)
            ->setMemo($response);
        return $paymentAck;
    }

    /**
     * @param string|null $response
     */
    public function sendAck($response = null)
    {
        $ack = $this->getAck($response)->serialize();
        $filename = 'r' . (string)time() . '.bitcoinpaymentACK';
        header('Content-Type: application/bitcoin-paymentack');

        header('Content-Disposition: inline; filename=' . $filename);
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        header('Content-Length: ' . (string)strlen($ack));
        echo $ack;
    }
}
