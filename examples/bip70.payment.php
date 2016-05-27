<?php

require_once "../vendor/autoload.php";

use \BitWasp\Bitcoin\PaymentProtocol\PaymentVerifier;
use \BitWasp\Bitcoin\PaymentProtocol\PaymentHandler;
use \BitWasp\Bitcoin\PaymentProtocol\HttpResponse;
use \BitWasp\Bitcoin\PaymentProtocol\Protobufs\Payment;
use \BitWasp\Bitcoin\PaymentProtocol\Protobufs\PaymentRequest;

$time = $_GET['time'];
$request = new PaymentRequest();
$request->parse(file_get_contents('/tmp/pr.bitcoin.'.$time));

$input = \Symfony\Component\HttpFoundation\Request::createFromGlobals();
$payment = new Payment();
$payment->parse($input->getContent());

$math = \BitWasp\Bitcoin\Bitcoin::getMath();
$verifier = new PaymentVerifier($math);
if ($verifier->checkPayment($request, $payment)) {
    $http = new HttpResponse();
    $handler = new PaymentHandler();

    $ack = $handler->getPaymentAck($payment, 'Optional hello message here!');
    $response = $http->paymentAck($ack);
    $response->send();
}