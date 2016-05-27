<?php

namespace BitWasp\Bitcoin\Tests\PaymentProtocol;

use BitWasp\Bitcoin\Crypto\Random\Random;
use BitWasp\Bitcoin\Math\Math;
use BitWasp\Bitcoin\PaymentProtocol\HttpResponse;
use BitWasp\Bitcoin\PaymentProtocol\PaymentHandler;
use BitWasp\Bitcoin\PaymentProtocol\PaymentVerifier;
use BitWasp\Bitcoin\PaymentProtocol\Protobufs\Output;
use BitWasp\Bitcoin\PaymentProtocol\Protobufs\Payment;
use BitWasp\Bitcoin\PaymentProtocol\Protobufs\PaymentACK;
use BitWasp\Bitcoin\PaymentProtocol\Protobufs\PaymentDetails;
use BitWasp\Bitcoin\PaymentProtocol\Protobufs\PaymentRequest;
use BitWasp\Bitcoin\PaymentProtocol\RequestBuilder;
use BitWasp\Bitcoin\PaymentProtocol\RequestSigner;
use BitWasp\Bitcoin\Script\Opcodes;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Transaction\Factory\TxBuilder;
use BitWasp\Bitcoin\Transaction\TransactionOutput;
use BitWasp\Buffertools\Buffer;

class WorkflowTest extends Bip70Test
{
    public function testEndToEnd()
    {
        $math = new Math();
        $random = new Random();
        // Step 0: Buyer clicks purchase on the website

        // Step 1: Website generates a payment request, 500000 satoshis
        // Request is saved somehow, and a URL given to the client.
        $http = new HttpResponse();
        $merchantRandom = $random->bytes(16);
        $destination = ScriptFactory::sequence([Opcodes::OP_DUP, Opcodes::OP_HASH160, $random->bytes(20), Opcodes::OP_EQUALVERIFY, Opcodes::OP_CHECKSIG]);
        $output = new TransactionOutput(500000, $destination);

        $requestSigner = RequestSigner::sha256($this->getKey(), $this->getCert());
        $requestBuilder = (new RequestBuilder())
            ->setTime(time())
            ->setExpires((new \DateTime('+1h'))->getTimestamp())
            ->setMemo('Payment for 1 shoes')
            ->setMerchantData($merchantRandom->getBinary())
            ->setNetwork('main')
            ->setOutputs([$output])
            ->setPaymentUrl('https://example.com/payment')
            ->setSigner($requestSigner)
        ;

        $request = $requestBuilder->getPaymentRequest();
        $sendRequest = $http->paymentRequest($request);
        $this->assertEquals($request->serialize(), $sendRequest->getContent());

        // Step 2: Client visits request url. Send request to client.

        $clientRequest = new PaymentRequest();
        $clientRequest->parse($sendRequest->getContent());

        // Client verifies the signature of the request
        $signer = RequestSigner::none();
        $this->assertTrue($signer->verify($clientRequest));

        // Step 3: Client authorizes payment

        $clientRequestDetails = new PaymentDetails();
        $clientRequestDetails->parse($clientRequest->getSerializedPaymentDetails());

        $changeScript = ScriptFactory::sequence([Opcodes::OP_DUP, Opcodes::OP_HASH160, $random->bytes(20), Opcodes::OP_EQUALVERIFY, Opcodes::OP_CHECKSIG]);
        $transaction = (new TxBuilder())
            ->input(new Buffer('', 32), 0)
            ->output(500000, $destination)
            ->output(1231231, $changeScript)
            ->get();

        $payment = new Payment();
        $payment->setMerchantData($clientRequestDetails->getMerchantData());
        $payment->setTransactions([$transaction->getBinary()]);

        $refundScript = ScriptFactory::sequence([Opcodes::OP_DUP, Opcodes::OP_HASH160, $random->bytes(20), Opcodes::OP_EQUALVERIFY, Opcodes::OP_CHECKSIG]);
        $refundOutput = new Output();
        $refundOutput->setScript($refundScript->getBinary());
        $payment->setRefundTo($refundOutput);

        $sendPayment = $http->payment($payment);
        $this->assertEquals($payment->serialize(), $sendPayment->getContent());

        // Step 4: Payment message is sent to server

        $serverPayment = new Payment();
        $serverPayment->parse($sendPayment->getContent());

        // Check payment corresponds to initial request
        $this->assertEquals($serverPayment->getMerchantData(), $clientRequestDetails->getMerchantData());

        // Verify payment against request
        $paymentVerifier = new PaymentVerifier($math);
        $this->assertTrue($paymentVerifier->checkPayment($request, $payment));

        // Create a PaymentACK
        $paymentHandler = new PaymentHandler();
        $ack = $paymentHandler->getPaymentAck($payment, 'thanks!');
        $sendAck = $http->paymentAck($ack);
        $this->assertEquals($ack->serialize(), $sendAck->getContent());

        // Step 5:
        $clientAck = new PaymentACK();
        $clientAck->parse($sendAck->getContent());
        $this->assertEquals('thanks!', $clientAck->getMemo());
    }
}
