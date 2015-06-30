<?php

require_once "../vendor/autoload.php";


use BitWasp\Bitcoin\Network\Structure\NetworkAddress;
use BitWasp\Bitcoin\Chain\BlockHashIndex;
use BitWasp\Bitcoin\Chain\BlockHeightIndex;
use BitWasp\Bitcoin\Chain\BlockIndex;
use BitWasp\Buffertools\Buffer;
use BitWasp\Bitcoin\Crypto\Random\Random;
use BitWasp\Bitcoin\Network\MessageFactory;
use BitWasp\Bitcoin\Network\P2P\Peer;
use BitWasp\Bitcoin\Rpc\RpcFactory;

$network = BitWasp\Bitcoin\Bitcoin::getDefaultNetwork();
$math = BitWasp\Bitcoin\Bitcoin::getMath();

$rpc = RpcFactory::bitcoind('192.168.192.101',8332, 'bitcoinrpc', 'rda0digjjfgsujushenbgtjegvrnrdybmvdkerb');
$loop = React\EventLoop\Factory::create();
$dnsResolverFactory = new React\Dns\Resolver\Factory();
$dns = $dnsResolverFactory->createCached('8.8.8.8', $loop);
$connector = new React\SocketClient\Connector($loop, $dns);

$redis = new Redis();
$redis->connect('127.0.0.1');

$mkCache = function ($namespace) use ($redis) {
    $cache = new \Doctrine\Common\Cache\RedisCache();
    $cache->setRedis($redis);
    $cache->setNamespace($namespace);
    return $cache;
};

$headerFS = $mkCache('headers');
$heightFS = $mkCache('height');
$hashFS = $mkCache('hash');

$headerchain = new \BitWasp\Bitcoin\Chain\Headerchain(
    $math,
    new \BitWasp\Bitcoin\Block\BlockHeader(
        '1',
        '0000000000000000000000000000000000000000000000000000000000000000',
        '4a5e1e4baab89f3a32518a88c31bc87f618f76673e2cc77ab2127b7afdeda33b',
        1231006505,
        \BitWasp\Buffertools\Buffer::hex('1d00ffff'),
        2083236893
    ),
    new \BitWasp\Bitcoin\Chain\HeaderStorage($headerFS),
    new BlockIndex(
        new BlockHashIndex($hashFS),
        new BlockHeightIndex($heightFS)
    )
);

$peers = new \BitWasp\Bitcoin\Network\BlockLocator();

$host = new NetworkAddress(
    Buffer::hex('01', 16),
    '91.146.57.187',
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

$peers = new \BitWasp\Bitcoin\Network\P2P\PeerLocator($local, $factory, $connector, $loop);
$node = new \BitWasp\Bitcoin\Network\P2P\Node($local, $headerchain, $peers);

$peers
->discoverPeers()
->then(
    function (\BitWasp\Bitcoin\Network\P2P\PeerLocator $locator) {
        return $locator->connectNextPeer();
    },
    function ($error) {
        echo $error;
        throw $error;
    })
->then(
    function (Peer $peer) use (&$node) {
        $peer->on('inv', function (Peer $peer, \BitWasp\Bitcoin\Network\Messages\Inv $inv) use (&$node) {
            $missedBlock = false;
            foreach ($inv->getItems() as $item) {
                if ($item->isBlock()) {
                    $key = $item->getHash()->getHex();
                    if (!$node->chain()->index()->hash()->contains($key)) {
                        $missedBlock = true;
                    }
                }
            }

            if ($missedBlock) {
                $peer->getheaders($node->locator(true));
            }
        });

        $peer->on('block', function (Peer $peer, \BitWasp\Bitcoin\Network\Messages\Block $block) use ($node) {
            $header = $block->getBlock()->getHeader();
            if (!$node->chain()->index()->hash()->contains($header->getBlockHash())) {
                $node->chain()->process($header);
            }
        });

        $peer->on('headers', function (Peer $peer, \BitWasp\Bitcoin\Network\Messages\Headers $headers) use ($node) {
            $vHeaders = $headers->getHeaders();
            $cHeaders = count($vHeaders);
            for ($i = 0; $i < $cHeaders; $i++) {
                $node->chain()->process($vHeaders[$i]);
            }

            echo "Now have up to " . $node->chain()->currentHeight() . " headers\n";
            if ($cHeaders > 0) {
                $peer->getheaders($node->locator(true));
            }
        });

        $peer->getheaders($node->locator(true));
    },
    function ($error) {
        echo $error;
        throw $error;
    }
);

$loop->run();




