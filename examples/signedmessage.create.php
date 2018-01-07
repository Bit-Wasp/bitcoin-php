<?php

require __DIR__ . "/../vendor/autoload.php";

use BitWasp\Bitcoin\Address\PayToPubKeyHashAddress;
use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Key\PrivateKeyFactory;
use BitWasp\Bitcoin\MessageSigner\MessageSigner;

$ec = Bitcoin::getEcAdapter();
$privateKey = PrivateKeyFactory::create(true);

$message = 'hi';

$signer = new MessageSigner($ec);
$signed = $signer->sign($message, $privateKey);
$address = new PayToPubKeyHashAddress($privateKey->getPublicKey()->getPubKeyHash());
echo sprintf("Signed by %s\n%s\n", $address->getAddress(), $signed->getBuffer()->getBinary());
