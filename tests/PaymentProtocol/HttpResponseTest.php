<?php

namespace BitWasp\Bitcoin\Tests\PaymentProtocol;

use BitWasp\Bitcoin\PaymentProtocol\HttpResponse;
use BitWasp\Bitcoin\PaymentProtocol\Protobufs\Payment;
use BitWasp\Bitcoin\PaymentProtocol\Protobufs\PaymentACK;
use BitWasp\Bitcoin\PaymentProtocol\Protobufs\PaymentDetails;
use BitWasp\Bitcoin\PaymentProtocol\Protobufs\PaymentRequest;

class HttpResponseTest extends Bip70Test
{

    public function getVectors()
    {
        $payment = new Payment();
        $paymentAck = new PaymentACK();
        $paymentAck->setPayment($payment);

        $details = new PaymentDetails();
        $details->setTime(time());
        $paymentRequest = new PaymentRequest();
        $paymentRequest->setSerializedPaymentDetails($details->serialize());
        return [
            ['payment', 'payment', $payment],
            ['paymentRequest', 'paymentrequest', $paymentRequest],
            ['paymentAck', 'paymentack', $paymentAck],
        ];
    }

    /**
     * @dataProvider getVectors
     * @param $method
     * @param $appType
     * @param Payment|PaymentRequest|PaymentACK $message
     */
    public function testAll($method, $appType, $message)
    {
        $http = new HttpResponse();
        $response = call_user_func_array([$http, $method], [$message]);

        $start = "inline; filename=";
        $this->assertEquals($message->serialize(), $response->getContent());
        $this->assertEquals('application/bitcoin-' . $appType, $response->headers->get('Content-Type'));
        $this->assertEquals($start, substr($response->headers->get('Content-Disposition'), 0, 17));
        $this->assertEquals('binary', $response->headers->get('Content-Transfer-Encoding'));
        $this->assertEquals('0', $response->headers->get('Expires'));
        $this->assertEquals('must-revalidate, post-check=0, pre-check=0', substr($response->headers->get('Cache-Control'), 0, 42));
    }
}
