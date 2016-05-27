<?php

require "../vendor/autoload.php";

use BitWasp\Bitcoin\PaymentProtocol\Protobufs\PaymentRequest;
use BitWasp\Bitcoin\PaymentProtocol\HttpResponse;

$time = $_GET['time'];

$request = new PaymentRequest();
$request->parse(file_get_contents('/tmp/pr.bitcoin.'.$time));

$http = new HttpResponse();
$response = $http->paymentRequest($request);
$response->send();