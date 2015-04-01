<?php

require_once "../vendor/autoload.php";

use \BitWasp\Bitcoin\PaymentProtocol\PaymentHandler;
use \BitWasp\Bitcoin\PaymentProtocol\Protobufs\PaymentRequest;

$time = $_GET['time'];
$input = file_get_contents("php://input");

$request = new PaymentRequest();
$request->parse(file_get_contents('/tmp/.abc'.$time));

$handler = new PaymentHandler($input);
if ($handler->checkAgainstRequest($request)) {
    $handler->sendAck('Thanks!');
}
