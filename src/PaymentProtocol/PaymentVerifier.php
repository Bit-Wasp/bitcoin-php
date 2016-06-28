<?php

namespace BitWasp\Bitcoin\PaymentProtocol;

use BitWasp\Bitcoin\Collection\Transaction\TransactionCollection;
use BitWasp\Bitcoin\Math\Math;
use BitWasp\Bitcoin\PaymentProtocol\Protobufs\Payment;
use BitWasp\Bitcoin\PaymentProtocol\Protobufs\PaymentDetails;
use BitWasp\Bitcoin\PaymentProtocol\Protobufs\PaymentRequest;
use BitWasp\Bitcoin\Transaction\TransactionFactory;
use BitWasp\Buffertools\Buffer;

class PaymentVerifier
{
    /**
     * @var Math
     */
    private $math;

    /**
     * PaymentVerifier constructor.
     * @param Math $math
     */
    public function __construct(Math $math)
    {
        $this->math = $math;
    }

    /**
     * @param Payment $payment
     * @return TransactionCollection
     */
    public function getTransactions(Payment $payment)
    {
        return new TransactionCollection(
            array_map(
                function ($binTx) {
                    return TransactionFactory::fromHex(new Buffer($binTx));
                },
                $payment->getTransactionsList()
            )
        );
    }

    /**
     * @param PaymentRequest $request
     * @param TransactionCollection $collection
     * @return bool
     */
    public function checkTransactions(PaymentRequest $request, TransactionCollection $collection)
    {
        // Add up cumulative amounts for each destination
        $scriptAmount = [];
        foreach ($collection as $tx) {
            foreach ($tx->getOutputs() as $output) {
                $scriptBin = $output->getScript()->getBinary();
                if (array_key_exists($scriptBin, $scriptAmount)) {
                    $scriptAmount[$scriptBin] = $this->math->add(gmp_init($output->getValue(), 10), $scriptAmount[$scriptBin]);
                } else {
                    $scriptAmount[$scriptBin] = gmp_init($output->getValue(), 10);
                }
            }
        }

        // Do the same for our PaymentDetails
        $details = new PaymentDetails();
        $details->parse($request->getSerializedPaymentDetails());

        $requiredAmounts = [];
        foreach ($details->getOutputsList() as $out) {
            $scriptBin = $out->getScript();
            if (array_key_exists($scriptBin, $requiredAmounts)) {
                $requiredAmounts[$scriptBin] = $this->math->add(gmp_init($out->getAmount(), 10), $requiredAmounts[$scriptBin]);
            } else {
                $requiredAmounts[$scriptBin] = gmp_init($out->getAmount(), 10);
            }
        }


        // Check required amounts against user transaction
        foreach ($requiredAmounts as $script => $value) {
            // Script not funded
            if (!array_key_exists($script, $scriptAmount)) {
                return false;
            }

            // Script not paid enough
            if ($this->math->cmp($scriptAmount[$script], $value) < 0) {
                return false;
            }
        }

        return true;
    }

    /**
     * Verifies that outputs of transactions in Payment
     * satisfy the amounts required by the PaymentRequest.
     *
     * @param Payment $payment
     * @param PaymentRequest $request
     * @return bool
     */
    public function checkPayment(PaymentRequest $request, Payment $payment)
    {
        $transactions = $this->getTransactions($payment);
        return $this->checkTransactions($request, $transactions);
    }
}
