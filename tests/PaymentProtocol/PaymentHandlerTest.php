<?php

namespace BitWasp\Bitcoin\Tests\PaymentProtocol;

use BitWasp\Bitcoin\Key\PublicKeyFactory;
use BitWasp\Bitcoin\PaymentProtocol\PaymentHandler;
use BitWasp\Bitcoin\PaymentProtocol\PaymentRequestBuilder;
use BitWasp\Bitcoin\PaymentProtocol\PaymentRequestSigner;
use BitWasp\Bitcoin\PaymentProtocol\Protobufs\Payment;
use BitWasp\Bitcoin\Script\Script;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Bitcoin\Transaction\OutPoint;
use BitWasp\Bitcoin\Transaction\Transaction;
use BitWasp\Bitcoin\Transaction\TransactionInput;
use BitWasp\Bitcoin\Collection\Transaction\TransactionInputCollection;
use BitWasp\Bitcoin\Transaction\TransactionOutput;
use BitWasp\Bitcoin\Collection\Transaction\TransactionOutputCollection;
use BitWasp\Buffertools\Buffer;

class PaymentHandlerTest extends AbstractTestCase
{

    public function getCert()
    {
        return __DIR__ . '/../Data/ssl/server.crt';
    }
    public function getKey()
    {
        return __DIR__ . '/../Data/ssl/server.key';
    }

    public function getSigner()
    {
        return new PaymentRequestSigner('x509+sha256', $this->getKey(), $this->getCert());
    }

    public function testHandlerForTrue()
    {
        $builder = new PaymentRequestBuilder($this->getSigner(), 'main', time());
        $builder->addOutput(new TransactionOutput(50, new Script()));
        $builder->addOutput(new TransactionOutput(50, new Script()));
        $request = $builder->getPaymentRequest();

        // Handler allows for payments to be spanned over multiple transactions.
        // We test the effects of this by having two outputs in the same transaction.
        $response = new Transaction(
            1,
            new TransactionInputCollection([
                new TransactionInput(new OutPoint(Buffer::hex('0000000000000000000000000000000000000000000000000000000000000000'), 0), new Script())
            ]),
            new TransactionOutputCollection([
                new TransactionOutput(51, new Script()),
                new TransactionOutput(49, new Script())
            ])
        );

        $payment = new Payment();
        $payment->setTransactions($response->getBinary());
        $paymentMsg = $payment->serialize();

        $handler = new PaymentHandler($paymentMsg);
        $this->assertTrue($handler->checkAgainstRequest($request));

    }

    public function testHandlerForFalseBecauseOfValue()
    {
        $builder = new PaymentRequestBuilder($this->getSigner(), 'main', time());
        $builder->addOutput(new TransactionOutput(50, new Script()));
        $request = $builder->getPaymentRequest();

        // 50 satoshis are being sent, but all to the intended place
        $response = new Transaction(
            1,
            new TransactionInputCollection([
                new TransactionInput(new OutPoint(Buffer::hex('0000000000000000000000000000000000000000000000000000000000000000'), 0), new Script())
            ]),
            new TransactionOutputCollection([
                new TransactionOutput(49, new Script()),
                new TransactionOutput(1, new Script(new Buffer('ae')))
            ])
        );

        $payment = new Payment();
        $payment->setTransactions($response->getBinary());
        $paymentMsg = $payment->serialize();

        $handler = new PaymentHandler($paymentMsg);
        $this->assertFalse($handler->checkAgainstRequest($request));
    }

    public function testHandlerForFalseBecauseOfScript()
    {
        $builder = new PaymentRequestBuilder($this->getSigner(), 'main', time());
        $pubkey = PublicKeyFactory::fromHex('0496b538e853519c726a2c91e61ec11600ae1390813a627c66fb8be7947be63c52da7589379515d4e0a604f8141781e62294721166bf621e73a82cbf2342c858ee');
        $builder->addOutput(new TransactionOutput(50, ScriptFactory::scriptPubKey()->payToPubKey($pubkey)));
        $request = $builder->getPaymentRequest();

        // The 50 satoshis are not going to the specified contract
        $response = new Transaction(
            1,
            new TransactionInputCollection([
                new TransactionInput(new OutPoint(new Buffer('', 32), 0), new Script())
            ]),
            new TransactionOutputCollection([
                new TransactionOutput(50, ScriptFactory::scriptPubKey()->payToScriptHash(new Script($pubkey->getBuffer()))),
                new TransactionOutput(1, new Script(new Buffer('ae')))
            ])
        );

        $payment = new Payment();
        $payment->setTransactions($response->getBinary());
        $paymentMsg = $payment->serialize();

        $handler = new PaymentHandler($paymentMsg);
        $this->assertFalse($handler->checkAgainstRequest($request));
    }

    public function testGetAck()
    {
        $payment = new Payment();
        $payment->setTransactions(hex2bin('010000000100000000000000000000000000000000000000000000000000000000000000000000000000ffffffff02320000000000000000010000000000000002616500000000'), 0);
        $paymentMsg = $payment->serialize();

        $handler = new PaymentHandler($paymentMsg);
        // Dont bother checking

        $ack = $handler->getAck('thanks for payment');
        $this->assertEquals('thanks for payment', $ack->getMemo());
        $this->assertEquals($payment, $ack->getPayment());
    }
}
