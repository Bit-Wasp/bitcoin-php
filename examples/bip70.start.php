<?php

require "../vendor/autoload.php";

use BitWasp\Bitcoin\Address\AddressFactory;
use BitWasp\Bitcoin\PaymentProtocol\PaymentRequestBuilder;
use BitWasp\Bitcoin\PaymentProtocol\PaymentRequestSigner;

$time = time();
$amount = 10000;
$destination = '18Ffckz8jsjU7YbhP9P44JMd33Hdkkojtc';
$paymentUrl = 'http://192.168.0.223:81/bitcoin-php/examples/bip70.fetch.php?time=' . $time;

// Create a signer for x509+sha256 - this requires a readable private key and certificate chain.
// $signer = new PaymentRequestSigner('none');
$signer = new PaymentRequestSigner('x509+sha256', '/var/www/git/paymentrequestold/.keys/ssl.key', '/var/www/git/paymentrequestold/.keys/ssl.pem');
$builder = new PaymentRequestBuilder($signer, 'main', time());

// PaymentRequests contain outputs that the wallet will fulfill
$address = AddressFactory::fromString($destination);
$builder->addAddressPayment($address, $amount);

// Create the request, write it to a temporary file
$request = $builder->getPaymentRequest();

// Create a url + display a QR
$encodedUrl = urlencode($paymentUrl);
$uri = "bitcoin:$address?r=$encodedUrl&amount=0.00010000";
$qr = urlencode($uri);

echo "<a href='$uri'>Pay<img src='https://chart.googleapis.com/chart?chs=300x300&cht=qr&chl=$qr'></a>";
