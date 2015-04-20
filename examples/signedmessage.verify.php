<?php

require "../vendor/autoload.php";

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\MessageSigner\MessageSigner;
use BitWasp\Bitcoin\Serializer\MessageSigner\SignedMessageSerializer;
use BitWasp\Bitcoin\Serializer\Signature\CompactSignatureSerializer;

Bitcoin::setNetwork(\BitWasp\Bitcoin\Network\NetworkFactory::bitcoinTestnet());

$address = 'n2Z2DFCxG6vktyX1MFkKAQPQFsrmniGKj5';

$sig = '-----BEGIN BITCOIN SIGNED MESSAGE-----
hi
-----BEGIN SIGNATURE-----
IBpGR29vEbbl4kmpK0fcDsT75GPeH2dg5O199D3iIkS3VcDoQahJMGJEDozXot8JGULWjN9Llq79aF+FogOoz/M=
-----END BITCOIN SIGNED MESSAGE-----';

$ec = Bitcoin::getEcAdapter();

$addr = \BitWasp\Bitcoin\Address\AddressFactory::fromString($address);
$serializer = new SignedMessageSerializer(new CompactSignatureSerializer(Bitcoin::getMath()));
$signedMessage = $serializer->parse($sig);

$signer = new MessageSigner($ec);
if ($signer->verify($signedMessage, $addr)) {
    echo "Signature verified!\n";
} else {
    echo "Failed to verify signature!\n";
}
