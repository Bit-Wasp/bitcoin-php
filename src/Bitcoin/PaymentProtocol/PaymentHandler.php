<?php

namespace BitWasp\Bitcoin\PaymentProtocol;

use BitWasp\Bitcoin\PaymentProtocol\Protobufs\PaymentRequest as PaymentRequestBuf;
use BitWasp\Bitcoin\PaymentProtocol\Protobufs\PaymentDetails as PaymentDetailsBuf;
use BitWasp\Bitcoin\PaymentProtocol\Protobufs\Payment as PaymentBuf;
use BitWasp\Bitcoin\PaymentProtocol\Protobufs\PaymentACK as PaymentACKBuf;
use BitWasp\Bitcoin\Transaction\TransactionCollection;
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
        $nOutputs = count($outputs);
        $found = 0;

        // Check that regardless of the other outputs, that each specific output was paid.
        foreach ($this->getTransactions()->getTransactions() as $tx) {
            foreach ($tx->getOutputs()->getOutputs() as $txOut) {
                foreach (array_keys($outputs) as $index) {
                    // Check the scripts/amounts match
                    if ($txOut->getScript()->getBinary() == $outputs[$index]->getScript()
                    && $txOut->getValue() == $outputs[$index]->getAmount()
                    ) {
                        unset($outputs[$index]);
                        if (++$found == $nOutputs) {
                            return true;
                        }
                    }
                }
            }
        }

        return false;
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
        $filename = "r" . (string)time() . ".bitcoinpaymentACK";
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
