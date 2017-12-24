<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\PaymentProtocol;

use BitWasp\Bitcoin\Crypto\Random\Random;
use BitWasp\Bitcoin\PaymentProtocol\Protobufs\Payment;
use BitWasp\Bitcoin\PaymentProtocol\Protobufs\PaymentACK;
use BitWasp\Bitcoin\PaymentProtocol\Protobufs\PaymentRequest;
use Symfony\Component\HttpFoundation\Response;

class HttpResponse
{
    /**
     * @param string $data
     * @param string $contentType
     * @return Response
     * @throws \BitWasp\Bitcoin\Exceptions\RandomBytesFailure
     */
    public function raw(string $data, string $contentType): Response
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
    public function paymentRequest(PaymentRequest $request): Response
    {
        return $this->raw($request->serialize(), 'paymentrequest');
    }

    /**
     * @param Payment $payment
     * @return Response
     */
    public function payment(Payment $payment): Response
    {
        return $this->raw($payment->serialize(), 'payment');
    }

    /**
     * @param PaymentACK $ack
     * @return Response
     */
    public function paymentAck(PaymentACK $ack): Response
    {
        return $this->raw($ack->serialize(), 'paymentack');
    }
}
