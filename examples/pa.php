<?php

require_once "../vendor/autoload.php";

use BitWasp\Bitcoin\Network\Structure\NetworkAddress;
use BitWasp\Buffertools\Buffer;

    $packet = new \BitWasp\Bitcoin\Network\Messages\Version(
        '70001',
        Buffer::hex('01', 16),
        time(),
        new NetworkAddress(Buffer::hex('00', 16), '75.86.177.167', 8333),
        new NetworkAddress(Buffer::hex('01', 16), '46.7.4.219', 8333),
        new Buffer("/Satoshi:0.7.2/"),
        212672,
        true
    );

    $network = \BitWasp\Bitcoin\Network\NetworkFactory::bitcoinTestnet();
    $msg = new \BitWasp\Bitcoin\Network\NetworkMessage($network, $packet);

$stream = stream_socket_client('tcp://192.168.192.101:8333');

