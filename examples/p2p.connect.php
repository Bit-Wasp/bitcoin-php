<?php

require_once "../vendor/autoload.php";

use BitWasp\Bitcoin\Network\Structure\NetworkAddress;
use BitWasp\Buffertools\Buffer;
use BitWasp\Bitcoin\Crypto\Random\Random;
use BitWasp\Bitcoin\Network\MessageFactory;
use BitWasp\Bitcoin\Network\P2P\Peer;
use BitWasp\Bitcoin\Network\Messages\Addr;

$network = BitWasp\Bitcoin\Bitcoin::getDefaultNetwork();

$loop = React\EventLoop\Factory::create();
$dnsResolverFactory = new React\Dns\Resolver\Factory();
$dns = $dnsResolverFactory->createCached('8.8.8.8', $loop);
$connector = new React\SocketClient\Connector($loop, $dns);

$host = new NetworkAddress(
    Buffer::hex('01', 16),
    '192.168.192.101',
    8333
);

$local = new NetworkAddress(
    Buffer::hex('01', 16),
    '192.168.192.39',
    32301
);

$factory = new MessageFactory(
    $network,
    new Random()
);

$peer = new Peer(
    $host,
    $local,
    $connector,
    $factory,
    $loop
);

$peer->on('ready', function (Peer $peer) use ($factory) {
    $peer->send($factory->getaddr());
    $peer->on('addr', function (Addr $addr) {
        echo "Nodes: \n";
        foreach ($addr->getAddresses() as $address)
        {
            echo $address->getIp() . "\n";
        }
    });
});

$peer->on('send', function ($msg) {
    echo " [ sending " . $msg->getCommand() . " ]\n";
});

$peer->on('version', function ($msg) {
    echo $msg->getNetworkMessage()->getHex() . "\n";
});

$peer->on('msg', function ($msg) {
    echo " [ received " . $msg->getCommand() . " ]\n";
});

$peer->connect();
$loop->run();
