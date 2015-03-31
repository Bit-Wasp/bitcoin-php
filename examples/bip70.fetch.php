<?php

require "../vendor/autoload.php";

use BitWasp\Bitcoin\PaymentProtocol\PaymentRequestBuilder;
use BitWasp\Bitcoin\PaymentProtocol\PaymentRequestSigner;

$time = $_GET['time'];

//$signer = new PaymentRequestSigner('x509+sha256', '/var/www/git/paymentrequestold/.keys/key.key', '/var/www/git/paymentrequestold/.keys/certificateChain.pem');
// This can be the regular signer, since the signature has already been added.
$signer = new PaymentRequestSigner('none');
$request = new PaymentRequestBuilder($signer, 'main', $time);
$request->parse(file_get_contents('/tmp/.abc'.$time));
$request->send();

