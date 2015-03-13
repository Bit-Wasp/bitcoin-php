
Bitcoin
=======
[![Build Status](https://travis-ci.org/afk11/bitcoin.svg?branch=master)](https://travis-ci.org/afk11/bitcoin)


#Presently supported:

 - Blocks, block headers, basic mining, difficulty calculations
 - ECDSA key creation, public & private key types. 
 - Transactions
 - Signature creation & verification 
 - Deterministic signatures (RFC6979)
 - BIP32 deterministic algorithms
 - Script builder, parser
 - RPC bindings to Bitcoin Core's RPC, getting OOP responses :)
 - Easy serialization to binary representation of most classes

#Todo: 

  - TransactionBuilder
  - Full script interpreter
  - NetworkMessageSerializer (for network messages, blocks, tx's)
  - SPV
  - P2P
  - EC Adapter to work with either phpecc or secp256k1-php


# Examples  
`
 // Create private keys
 use Afk11\Bitcoin\Key\PrivateKeyFactory;
 
 $private = PrivateKeyFactory:create(true);
 $public = $private->getPublicKey();
 echo $private->getBuffer();
 echo $public->getBuffer();
`

`
// Explore the Blockchain using OOP bindings to the daemon
use Afk11\Bitcoin\Rpc\RpcFactory;

$bitcoind = RpcFactory::bitcoind('127.0.0.1', 18332, 'bitcoinrpc', 'BBpsLqmCCx7Vp8sRd5ygDxFkHZBgWLTTi55QwWgN6Ng6');

$hash = $bitcoind->getbestblockhash();
$block = $bitcoind->getblock($hash);
`