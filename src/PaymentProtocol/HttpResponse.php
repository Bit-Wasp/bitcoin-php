<?php

namespace BitWasp\Bitcoin\PaymentProtocol;

use BitWasp\Bitcoin\Crypto\Random\Random;
use BitWasp\Bitcoin\PaymentProtocol\Protobufs\Payment;
use BitWasp\Bitcoin\PaymentProtocol\Protobufs\PaymentACK;
use BitWasp\Bitcoin\PaymentProtocol\Protobufs\PaymentRequest;
use Symfony\Component\HttpFoundation\Response;

class HttpResponse
{
    /**
     * @param $data
     * @param $contentType
     * @return Response
     */
    public function raw($data, $contentType)
    {
        $random = new Random();
        $filename = "r" . $random->bytes(12)->getHex() . "." . $contentType;

        $response = new Response();
        $response->setContent($data);

        $response->headers->set('Content-Type', 'application/bitcoin-' . $contentType);
        $response->headers->set('Content-Disposition', 'inline; filename=' . $filename);
        $response->headers->set('Content-Transfer-Encoding', 'binary');
        $response->headers->set('Expires', '0');
        $response->headers->set('Cache-Control', 'must-revalidate, post-check=0, pre-check=0');

        return $response;
    }
    
    /**
     * @param PaymentRequest $request
     * @return Response
     */
    public function paymentRequest(PaymentRequest $request)
    {
        return $this->raw($request->serialize(), 'paymentrequest');
    }

    /**
     * @param Payment $payment
     * @return Response
     */
    public function payment(Payment $payment)
    {
        return $this->raw($payment->serialize(), 'payment');
    }

    /**
     * @param PaymentACK $ack
     * @return Response
     */
    public function paymentAck(PaymentACK $ack)
    {
        return $this->raw($ack->serialize(), 'paymentack');
    }
}
