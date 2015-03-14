
##Bitcoin
=======
[![Build Status](https://scrutinizer-ci.com/g/afk11/bitcoin/badges/build.png?b=master)](https://scrutinizer-ci.com/g/afk11/bitcoin/build-status/master)
[![Code Coverage](https://scrutinizer-ci.com/g/afk11/bitcoin/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/afk11/bitcoin/?branch=master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/afk11/bitcoin/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/afk11/bitcoin/?branch=master)

## Requirements
 * PHP 5.4+
 * Composer
 * ext-gmp
 * ext-mcrypt

##Installation
You can install this library via Composer:
`composer require afk11/bitcoin`
or 
```{
    "require": "afk11\bitcoin"
}```

##Presently supported:

 - Blocks, block headers, basic mining, difficulty calculations
 - ECDSA key creation, public & private key types. 
 - Transactions
 - Signature creation & verification 
 - Deterministic signatures (RFC6979)
 - BIP32 deterministic algorithms
 - Script builder, parser
 - RPC bindings to Bitcoin Core's RPC, getting OOP responses :)
 - Easy serialization to binary representation of most classes

##Todo:
  - TransactionBuilder
  - Full script interpreter
  - NetworkMessageSerializer (for network messages, blocks, tx's)
  - SPV
  - P2P
  - EC Adapter to work with either phpecc or secp256k1-php
  
# Examples  
## Generate private keys
```
 // Create private keys
 use Afk11\Bitcoin\Key\PrivateKeyFactory;
 
 $network = Bitcoin::GetNetwork();
 $private = PrivateKeyFactory:create(true);
 $public = $private->getPublicKey();
 echo $private->getBuffer() . "\n";
 echo $public->getBuffer() . "\n";
 echo $public->getAddress($network) . "\n";
```

## Explore the blockchain using OOP bindings to the RPFC
```
use Afk11\Bitcoin\Rpc\RpcFactory;

$bitcoind = RpcFactory::bitcoind('127.0.0.1', 18332, 'bitcoinrpc', 'BBpsLqmCCx7Vp8sRd5ygDxFkHZBgWLTTi55QwWgN6Ng6');

$hash = $bitcoind->getbestblockhash();
$block = $bitcoind->getblock($hash);
$tx = $bitcoind->getTransactions()->getTransaction(10);
echo $tx->getTransactionId();
```
