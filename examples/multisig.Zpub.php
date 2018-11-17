<?php

declare(strict_types=1);

use BitWasp\Bitcoin\Address\AddressCreator;
use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Key\Deterministic\HdPrefix\GlobalPrefixConfig;
use BitWasp\Bitcoin\Key\Deterministic\HdPrefix\NetworkConfig;
use BitWasp\Bitcoin\Key\Factory\HierarchicalKeyFactory;
use BitWasp\Bitcoin\Network\Slip132\BitcoinRegistry;
use BitWasp\Bitcoin\Key\Deterministic\Slip132\Slip132;
use BitWasp\Bitcoin\Key\KeyToScript\KeyToScriptHelper;
use BitWasp\Bitcoin\Network\NetworkFactory;
use BitWasp\Bitcoin\Serializer\Key\HierarchicalKey\Base58ExtendedKeySerializer;
use BitWasp\Bitcoin\Serializer\Key\HierarchicalKey\ExtendedKeySerializer;

require __DIR__ . "/../vendor/autoload.php";

$adapter = Bitcoin::getEcAdapter();
$btc = NetworkFactory::bitcoin();

// Here are two ACCOUNT public keys
$key1 = "Zpub74qd5RNQomhXkYCzxSU1QUcLjpN72EV3FRJXNXWbTTiLxwtXhK6jrAccYri3iEZzhzUvBRMMfFvjfWkeXrdj3ft23y2DqcVhPqz6f1LQjXE";
$key2 = "Zpub74hsLNTzMgUSkfez9LpE5o3esyWP1YGK4SftNir3c6xTEAoBWhmrFB86XY1VZaDLyqpbmyvfsxxT6D6crTT5oKxVViZQ5tuAfLjGe5N7HY3";

// Initialize Slip132 and produce the p2wsh multisig prefixes + factory
$slip132 = new Slip132(new KeyToScriptHelper($adapter));
$ZpubPrefix = $slip132->p2wshMultisig($m = 2, $n = 2, $sortCosignKeys = true, new BitcoinRegistry());

// NetworkConfig and GlobalPrefixConfig should be set
// with the minimum features required for your application,
// otherwise you'll accept keys you didn't want.
$serializer = new Base58ExtendedKeySerializer(new ExtendedKeySerializer($adapter, new GlobalPrefixConfig([
    new NetworkConfig($btc, [$ZpubPrefix,])
])));

$hkFactory = new HierarchicalKeyFactory($adapter, $serializer);

$multisigHdKeys = [
    $hkFactory->fromExtended($key1, $btc),
    $hkFactory->fromExtended($key2, $btc)
];

echo "Initialize multisignature account: M/48'/0'/0'/2'\n";
$accountNode = $hkFactory->multisig($ZpubPrefix->getScriptDataFactory(), ...$multisigHdKeys);
$receivingNode = $accountNode->deriveChild(0);

// Print out the parent public keys of the address chain
foreach ($receivingNode->getKeys() as $cosignerIdx => $cosignerKey) {
    // because we use SLIP132, we cannot use toExtended* methods on the HK.
    // a serializer initialized with our prefix is requried (unlike multisig.old.php example)
    echo "account key for cosigner $cosignerIdx {$serializer->serialize($btc, $cosignerKey)}\n";
}

$addrNode = $receivingNode->deriveChild(0);
$addrCreator = new AddressCreator();
$address = $addrNode->getAddress($addrCreator);
echo "address: {$address->getAddress()}\n";
