<?php

require "../vendor/autoload.php";

use BitWasp\Bitcoin\Rpc\RpcFactory;

$host = '127.0.0.1';
$port = '8332';
$user = getenv('BITCOINLIB_RPC_USER') ?: 'bitcoinrpc';
$pass = getenv('BITCOINLIB_RPC_PASSWORD') ?: '10739t763450123947123kasdkhfanwgdfawdnfkajwdgkagw';

$bitcoind = RpcFactory::bitcoind($host, $port, $user, $pass);

$block = $bitcoind->getblock($bitcoind->getblockhash(10));
print_r($block);