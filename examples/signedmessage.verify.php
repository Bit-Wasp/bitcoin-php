<?php

require __DIR__ . "/../vendor/autoload.php";

use BitWasp\Bitcoin\Address\AddressCreator;
use BitWasp\Bitcoin\Address\PayToPubKeyHashAddress;
use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Crypto\EcAdapter\EcSerializer;
use BitWasp\Bitcoin\Crypto\EcAdapter\Serializer\Signature\CompactSignatureSerializerInterface;
use BitWasp\Bitcoin\MessageSigner\MessageSigner;
use BitWasp\Bitcoin\Network\NetworkFactory;
use BitWasp\Bitcoin\Serializer\MessageSigner\SignedMessageSerializer;

Bitcoin::setNetwork(NetworkFactory::bitcoinTestnet());
$addrCreator = new AddressCreator();

$address = 'n2Z2DFCxG6vktyX1MFkKAQPQFsrmniGKj5';

$sig = '-----BEGIN BITCOIN SIGNED MESSAGE-----
hi
-----BEGIN SIGNATURE-----
IBpGR29vEbbl4kmpK0fcDsT75GPeH2dg5O199D3iIkS3VcDoQahJMGJEDozXot8JGULWjN9Llq79aF+FogOoz/M=
-----END BITCOIN SIGNED MESSAGE-----';

/** @var PayToPubKeyHashAddress $address */
$address = $addrCreator->fromString($address);

/** @var CompactSignatureSerializerInterface $compactSigSerializer */
$compactSigSerializer = EcSerializer::getSerializer(CompactSignatureSerializerInterface::class);
$serializer = new SignedMessageSerializer($compactSigSerializer);

$signedMessage = $serializer->parse($sig);
$signer = new MessageSigner();
if ($signer->verify($signedMessage, $address)) {
    echo "Signature verified!\n";
} else {
    echo "Failed to verify signature!\n";
}
