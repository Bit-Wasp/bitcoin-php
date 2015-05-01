<?php
require "../vendor/autoload.php";

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\MessageSigner\MessageSigner;
use BitWasp\Bitcoin\Key\PrivateKeyFactory;

$ec = Bitcoin::getEcAdapter();
$privateKey = PrivateKeyFactory::create(true);

$message = 'hi';

$signer = new MessageSigner($ec);
$signed = $signer->sign($message, $privateKey);

echo sprintf("Signed by %s\n%s\n", $privateKey->getAddress()->getAddress(), $signed->getBuffer()->getBinary());
