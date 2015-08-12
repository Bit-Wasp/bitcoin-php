<?php

require_once "vendor/autoload.php";

$math = \BitWasp\Bitcoin\Bitcoin::getMath();
$G = \BitWasp\Bitcoin\Bitcoin::getGenerator();

$adapter = \BitWasp\Bitcoin\Crypto\EcAdapter\EcAdapterFactory::getAdapter($math, $G);
$phpecc = \BitWasp\Bitcoin\Crypto\EcAdapter\EcAdapterFactory::getPhpEcc($math, $G);
$secp256k1 = \BitWasp\Bitcoin\Crypto\EcAdapter\EcAdapterFactory::getSecp256k1($math, $G);

$p1 = $phpecc->getPrivateKey(1);
$p2 = $secp256k1->getPrivateKey(1);

echo $p1->getHex() . "\n";
echo $p2->getHex() . "\n";

$pub1 = $p1->getPublicKey();
$pub2 = $p2->getPublicKey();

echo $pub1->getHex() . "\n";
echo $pub2->getHex() . "\n";

$hash = \BitWasp\Buffertools\Buffer::hex('41', 32);

$s1 = $phpecc->sign($hash, $p1);
$s2 = $phpecc->sign($hash, $p2);

echo $s1->getHex() . "\n";
echo $s2->getHex() . "\n";

$v1 = $phpecc->verify($hash, $pub1, $s1);
$v2 = $phpecc->verify($hash, $pub2, $s2);

echo "V1 - " .(string)$v1 . "\n";
echo "V2 - " .(string)$v2 . "\n";

$a1 = $p1->tweakAdd(20);
$a2 = $p2->tweakAdd(20);

echo $a1->getSecretMultiplier() . "\n";
echo $a2->getSecretMultiplier() . "\n";

$m1 = $p1->tweakMul(20);
$m2 = $p2->tweakMul(20);

echo $m1->getSecretMultiplier() . "\n";
echo $m2->getSecretMultiplier() . "\n";

$Pa1 = $pub1->tweakAdd(20);
$Pa2 = $pub2->tweakAdd(20);

echo $Pa1->getHex() . "\n";
echo $Pa2->getHex() . "\n";

$Pm1 = $pub1->tweakMul(20);
$Pm2 = $pub2->tweakMul(20);

echo $Pm1->getHex() . "\n";
echo $Pm2->getHex() . "\n";

