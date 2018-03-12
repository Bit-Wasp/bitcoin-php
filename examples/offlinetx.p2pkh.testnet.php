<?php

require_once __DIR__ . "/../vendor/autoload.php";

use BitWasp\Bitcoin\Address\AddressCreator;
use BitWasp\Bitcoin\Address\PayToPubKeyHashAddress;
use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Key\Factory\PrivateKeyFactory;
use BitWasp\Bitcoin\Network\NetworkFactory;
use BitWasp\Bitcoin\Transaction\Factory\Signer;
use BitWasp\Bitcoin\Transaction\TransactionFactory;

Bitcoin::setNetwork(NetworkFactory::bitcoinTestnet());
$network = Bitcoin::getNetwork();
$ecAdapter = Bitcoin::getEcAdapter();
$addrCreator = new AddressCreator();
$privFactory = PrivateKeyFactory::uncompressed();

$privateKey = $privFactory->fromHex('17a2209250b59f07a25b560aa09cb395a183eb260797c0396b82904f918518d5');
$address = new PayToPubKeyHashAddress($privateKey->getPublicKey()->getPubKeyHash());
echo "[Key: " . $privateKey->toWif($network) . " Address " . $address->getAddress($network) . "]\n";

$txHex = '010000000114a2856f5a2992a4ca0814be16a0ae79e2f88a6f53a20fcbcad5249165f56ee7010000006a47304402201e733603ac36239010e05ad229b4a18411d5507950f696db0771a5b7fe8e051202203c46da7e970e89cbbdfb4ee62fa775597a32e5029ab1d2a94f786999df2c2fd201210271127f11b833239aefd400b11d576e7cc48c6969c8e5f8e30b0f5ec0a514edf7feffffff02801a0600000000001976a914c4126d1b70f5667e492e3301c3aa8bf1031e21a888ac75a29d1d000000001976a9141ef8d6913c289890a5e9ec249fedde4440877d0288ac88540500';
$myTx = TransactionFactory::fromHex($txHex);
$spendOutput = 0;
$recipient = $addrCreator->fromString('n1b2a9rFvuU9wBgBaoWngNvvMxRV94ke3x');

echo "[Send to: " . $recipient->getAddress($network) . " \n";

echo "Generate a transaction spending the one above \n";
$spendTx = TransactionFactory::build()
    ->spendOutputFrom($myTx, $spendOutput)
    ->payToAddress(40000, $recipient)
    ->get();

echo "Sign transaction\n";
$signer = new Signer($spendTx, $ecAdapter);
$signer->sign(0, $privateKey, $myTx->getOutput($spendOutput));

echo "Generate transaction: \n";
$new = $signer->get();

echo $new->getHex()."\n";
