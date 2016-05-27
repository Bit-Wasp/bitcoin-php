<?php

namespace BitWasp\Bitcoin\Tests\PaymentProtocol;

use BitWasp\Bitcoin\Math\Math;
use BitWasp\Bitcoin\PaymentProtocol\PaymentVerifier;
use BitWasp\Bitcoin\PaymentProtocol\Protobufs\Payment;
use BitWasp\Bitcoin\PaymentProtocol\Protobufs\PaymentRequest;
use BitWasp\Bitcoin\PaymentProtocol\RequestBuilder;
use BitWasp\Bitcoin\Script\Opcodes;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Transaction\Factory\TxBuilder;
use BitWasp\Bitcoin\Transaction\TransactionOutput;
use BitWasp\Buffertools\Buffer;
use BitWasp\Bitcoin\Crypto\Random\Random;

class PaymentVerifierTest extends Bip70Test
{
    public function createVectors()
    {
        $math = new Math();
        $builder = new RequestBuilder();
        $builder->setTime(time());

        $random = new Random();
        $hash = $random->bytes(2);
        $p2pkh = ScriptFactory::sequence([Opcodes::OP_DUP, Opcodes::OP_HASH160, $hash, Opcodes::OP_EQUALVERIFY, Opcodes::OP_CHECKSIG]);
        return [
            [
                $math,
                (new Payment())
                ->setTransactions([
                    (new TxBuilder())
                    ->input(new Buffer('', 32), 0)
                    ->output(1, $p2pkh)
                    ->get()
                    ->getBinary()
                ]),
                $builder->setOutputs([new TransactionOutput(1, $p2pkh)])->getPaymentRequest(),
                'txout matches request exactly'
            ],
            [
                $math,
                (new Payment())
                    ->setTransactions([
                        (new TxBuilder())
                            ->input(new Buffer('', 32), 0)
                            ->output(2, $p2pkh)
                            ->get()
                            ->getBinary()
                    ]),
                $builder->setOutputs([new TransactionOutput(1, $p2pkh), new TransactionOutput(1, $p2pkh)])->getPaymentRequest(),
                'single output satisfies both'
            ],
            [
                $math,
                (new Payment())
                    ->setTransactions([
                        (new TxBuilder())
                            ->input(new Buffer('', 32), 0)
                            ->output(2, $p2pkh)
                            ->get()
                            ->getBinary()
                    ]),
                $builder->setOutputs([new TransactionOutput(1, $p2pkh)])->getPaymentRequest(),
                'overpayment to request address'
            ],
            [
                $math,
                (new Payment())
                    ->setTransactions([
                        (new TxBuilder())
                            ->input(new Buffer('', 32), 0)
                            ->output(1, $p2pkh)
                            ->get()
                            ->getBinary(),
                        (new TxBuilder())
                            ->input(new Buffer('', 32), 1)
                            ->output(1, $p2pkh)
                            ->get()
                            ->getBinary()
                    ]),
                $builder->setOutputs([new TransactionOutput(2, $p2pkh)])->getPaymentRequest(),
                'two transactions which together satisfy the request'
            ]
        ];
    }

    /**
     * @param Math $math
     * @param Payment $payment
     * @param PaymentRequest $request
     * @dataProvider createVectors
     */
    public function testVerifier(Math $math, Payment $payment, PaymentRequest $request, $message)
    {
        $verifier = new PaymentVerifier($math);
        $transactions = $verifier->getTransactions($payment);
        $this->assertTrue($verifier->checkTransactions($request, $transactions), $message);
        $this->assertTrue($verifier->checkPayment($request, $payment), $message);
    }

    public function createInvalidVectors()
    {
        $math = new Math();
        $builder = new RequestBuilder();
        $builder->setTime(time());

        $random = new Random();
        $hash = $random->bytes(20);
        $p2pkh = ScriptFactory::sequence([Opcodes::OP_DUP, Opcodes::OP_HASH160, $hash, Opcodes::OP_EQUALVERIFY, Opcodes::OP_CHECKSIG]);

        $hash2 = $random->bytes(20);
        $p2pkh2 = ScriptFactory::sequence([Opcodes::OP_DUP, Opcodes::OP_HASH160, $hash2, Opcodes::OP_EQUALVERIFY, Opcodes::OP_CHECKSIG]);
        return [
            [
                $math,
                (new Payment())
                    ->setTransactions([
                        (new TxBuilder())
                            ->input(new Buffer('', 32), 0)
                            ->output(999, $p2pkh)
                            ->get()
                            ->getBinary()
                    ]),
                $builder->setOutputs([new TransactionOutput(1000, $p2pkh)])->getPaymentRequest(),
                'underpayment from single output'
            ],
            [
                $math,
                (new Payment())
                    ->setTransactions([
                        (new TxBuilder())
                            ->input(new Buffer('', 32), 0)
                            ->output(1000, $p2pkh2)
                            ->get()
                            ->getBinary()
                    ]),
                $builder->setOutputs([new TransactionOutput(1000, $p2pkh)])->getPaymentRequest(),
                'pays to wrong address'
            ]
        ];
    }

    /**
     * @param Math $math
     * @param Payment $payment
     * @param PaymentRequest $request
     * @dataProvider createInvalidVectors
     */
    public function testInvalidPayment(Math $math, Payment $payment, PaymentRequest $request, $message)
    {
        $verifier = new PaymentVerifier($math);
        $transactions = $verifier->getTransactions($payment);
        $this->assertFalse($verifier->checkTransactions($request, $transactions), $message);
    }
}
