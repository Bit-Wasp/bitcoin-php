<?php

require "../vendor/autoload.php";

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\MessageSigner\MessageSigner;
use BitWasp\Bitcoin\Address\PayToPubKeyHashAddress;
use BitWasp\Bitcoin\Crypto\EcAdapter\Serializer\Signature\CompactSignatureSerializerInterface;

Bitcoin::setNetwork(\BitWasp\Bitcoin\Network\NetworkFactory::bitcoinTestnet());

$address = 'n2Z2DFCxG6vktyX1MFkKAQPQFsrmniGKj5';

$sig = '-----BEGIN BITCOIN SIGNED MESSAGE-----
hi
-----BEGIN SIGNATURE-----
IBpGR29vEbbl4kmpK0fcDsT75GPeH2dg5O199D3iIkS3VcDoQahJMGJEDozXot8JGULWjN9Llq79aF+FogOoz/M=
-----END BITCOIN SIGNED MESSAGE-----';

$ec = Bitcoin::getEcAdapter();

/** @var PayToPubKeyHashAddress $addr */
$addr = \BitWasp\Bitcoin\Address\AddressFactory::fromString($address);

/** @var CompactSignatureSerializerInterface $cs */
$cs = \BitWasp\Bitcoin\Crypto\EcAdapter\EcSerializer::getSerializer($ec, CompactSignatureSerializerInterface::class);
$serializer = new \BitWasp\Bitcoin\Serializer\MessageSigner\SignedMessageSerializer($cs);

$signedMessage = $serializer->parse($sig);

$signer = new MessageSigner($ec);
if ($signer->verify($signedMessage, $addr)) {
    echo "Signature verified!\n";
} else {
    echo "Failed to verify signature!\n";
}
