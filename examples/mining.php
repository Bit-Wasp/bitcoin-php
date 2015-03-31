<?php

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Network\NetworkFactory;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Key\PrivateKeyFactory;
use BitWasp\Bitcoin\Rpc\RpcFactory;
use BitWasp\Bitcoin\Miner\Miner;

require __DIR__ . "/../vendor/autoload.php";

// init network (TESTNET)
Bitcoin::setNetwork(NetworkFactory::bitcoinTestnet());

// generate a privatekey so we can received the BTC
$privKey = PrivateKeyFactory::create(true);
var_dump($privKey->toWif());

// get latest block from RPC
$rpc = RpcFactory::bitcoind(
    getenv('BITCOINLIB_RPC_HOST') ?: 'localhost', 
    "18332",
    getenv('BITCOINLIB_RPC_USER') ?: 'bitcoin', 
    getenv('BITCOINLIB_RPC_PASSWORD') ?: 'YOUR_PASSWORD'
);

$latest = $rpc->getblock($rpc->getbestblockhash());

// mining in the future \o/
$timestamp = time() + (3600 * 2);

// create script to pay ourselves
$script = ScriptFactory::scriptPubKey()->payToPubKey($privKey->getPublicKey());

// init miner
$miner = new Miner(Bitcoin::getMath(), $latest->getHeader(), $script, null, $timestamp, 2, true);

// let's GO!
var_dump("mining!");
$block = $miner->run();

// result
var_dump($block->getHeader()->getBlockHash());
echo $block->getBuffer();
