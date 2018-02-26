<?php

declare(strict_types=1);

use BitWasp\Bitcoin\Address\AddressCreator;
use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Key\Deterministic\HdPrefix\GlobalPrefixConfig;
use BitWasp\Bitcoin\Key\Deterministic\HdPrefix\NetworkConfig;
use BitWasp\Bitcoin\Network\Slip132\BitcoinRegistry;
use BitWasp\Bitcoin\Key\Deterministic\Slip132\Slip132;
use BitWasp\Bitcoin\Key\KeyToScript\KeyToScriptHelper;
use BitWasp\Bitcoin\Network\NetworkFactory;
use BitWasp\Bitcoin\Serializer\Key\HierarchicalKey\Base58ExtendedKeySerializer;
use BitWasp\Bitcoin\Serializer\Key\HierarchicalKey\ExtendedKeySerializer;

require __DIR__ . "/../vendor/autoload.php";

$adapter = Bitcoin::getEcAdapter();
$btc = NetworkFactory::bitcoin();

$slip132 = new Slip132(new KeyToScriptHelper($adapter));
$bitcoinPrefixes = new BitcoinRegistry();
$zpubPrefix = $slip132->p2wpkh($bitcoinPrefixes);

$config = new GlobalPrefixConfig([
    new NetworkConfig($btc, [
        $zpubPrefix,
    ])
]);

$serializer = new Base58ExtendedKeySerializer(
    new ExtendedKeySerializer($adapter, $config)
);

$rootKey = $serializer->parse($btc, "zprvAWgYBBk7JR8GiuMByuy3PBgDdCdBk3fBK77VSGEMnWT1gKG7hz5z9Jt1tPCA2itCvzowhWh5yMdGwyLcLmuKmC8RwgPZMcdCfvyVLhmUR2m");

$account0Key = $rootKey->derivePath("84'/0'/0'");
$firstKey = $account0Key->derivePath("0/0");
$address = $firstKey->getAddress(new AddressCreator());
echo $address->getAddress() . PHP_EOL;
