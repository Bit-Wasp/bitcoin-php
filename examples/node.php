<?php

require_once "../vendor/autoload.php";

use BitWasp\Bitcoin\Network\Structure\InventoryVector;
use BitWasp\Bitcoin\Network\Structure\NetworkAddress;
use BitWasp\Bitcoin\Chain\Blockchain;
use BitWasp\Bitcoin\Chain\BlockStorage;
use Doctrine\Common\Cache\ArrayCache;
use BitWasp\Bitcoin\Chain\BlockHashIndex;
use BitWasp\Bitcoin\Chain\BlockHeightIndex;
use BitWasp\Bitcoin\Chain\BlockIndex;
use BitWasp\Buffertools\Buffer;
use BitWasp\Bitcoin\Utxo\UtxoSet;
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

$blockchain = new Blockchain(
    $math,
    new \BitWasp\Bitcoin\Block\Block(
        $math,
        new \BitWasp\Bitcoin\Block\BlockHeader(
            '1',
            '0000000000000000000000000000000000000000000000000000000000000000',
            '4a5e1e4baab89f3a32518a88c31bc87f618f76673e2cc77ab2127b7afdeda33b',
            1231006505,
            \BitWasp\Buffertools\Buffer::hex('1d00ffff'),
            2083236893
        )
    ),
    new BlockStorage(new ArrayCache()),
    new BlockIndex(
        new BlockHashIndex(new ArrayCache()),
        new BlockHeightIndex(new ArrayCache())
    ),
    new UtxoSet(new ArrayCache())
);


$locator = new \BitWasp\Bitcoin\Network\BlockLocator();

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

// init
$peer->on('ready', function (Peer $peer) use ($factory) {
    echo "version exchanged\n";
});
$peer->connect()->then(function (Peer $peer) use (&$blockchain, $locator, $loop) {

    $peer->on('headers', function (Peer $peer, \BitWasp\Bitcoin\Network\Messages\Headers $headers) {
        foreach ($headers->getHeaders() as $h) {
            echo $h->getBlockHash() . "\n";
        }
    });
    $peer->on('inv', function (Peer $peer, \BitWasp\Bitcoin\Network\Messages\Inv $inv) {
        for ($i = 0; $i < count($inv); $i += 100) {
            $peer->send($peer->msgs()->getdata($inv->getItems()));
        }
    });

    $peer->on('block', function (Peer $peer, \BitWasp\Bitcoin\Network\Messages\Block $block) use (& $blockchain, $locator) {
        //echo "received block for processing \n";
        $blk = $block->getBlock();
        echo "received block\n";
        if (!$blockchain->index()->height()->contains($blk->getHeader()->getPrevBlock())) {
            echo "was not in chain\n";
            $peer->send(
                $peer->msgs()
                    ->getdata([
                        new InventoryVector(
                            InventoryVector::MSG_BLOCK,
                            Buffer::hex($blk->getHeader()->getPrevBlock())
                        )
                    ])
            )
            ;

            $peer->send(
                $peer->msgs()
                    ->getblocks(60000, [Buffer::hex($blk->getHeader()->getPrevBlock())])
            )
            ;
            $peer->send(
                $peer->msgs()
                    ->getheaders(60000, [Buffer::hex($blk->getHeader()->getPrevBlock())])
            )
            ;
            $peer->send(
                $peer->msgs()
                    ->getblocks(1, [Buffer::hex($blk->getHeader()->getPrevBlock())])
            )
            ;
        }

        $blockchain->process($blk);
    });
});

$loop->run();
