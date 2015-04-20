<?php
require "../vendor/autoload.php";

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\MessageSigner\MessageSigner;

$message = 'hi';
$privateKey = \BitWasp\Bitcoin\Key\PrivateKeyFactory::create(true);


$ec = Bitcoin::getEcAdapter();
$signer = new MessageSigner($ec);
$signed = $signer->sign($message, $privateKey);
echo "Signed by " . $privateKey->getAddress()->getAddress() . "\n";

$output = $signed->getBuffer()->getBinary();
echo $output . "\n";
