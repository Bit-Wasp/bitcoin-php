  
##Bitcoin
=======
[![Build Status](https://scrutinizer-ci.com/g/bit-wasp/bitcoin-php/badges/build.png?b=master)](https://scrutinizer-ci.com/g/bit-wasp/bitcoin-php/build-status/master)
[![Code Coverage](https://scrutinizer-ci.com/g/bit-wasp/bitcoin-php/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/bit-wasp/bitcoin-php/?branch=master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/Bit-Wasp/bitcoin-php/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/Bit-Wasp/bitcoin-php/?branch=master)
[![Join the chat at https://gitter.im/Bit-Wasp/bitcoin-php](https://badges.gitter.im/Join%20Chat.svg)](https://gitter.im/Bit-Wasp/bitcoin-php?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge&utm_content=badge)
 
## Requirements:
 * PHP 5.4+
 * Composer
 * ext-gmp
 * ext-mcrypt

## Optional:
 * [[secp256k1-php](https://github.com/Bit-Wasp/secp256k1-php)] - Install the secp256k1 PHP extension for blazing speeds.

##Installation
You can install this library via Composer:
`composer require bitwasp/bitcoin-php`
or 
```{
    "require": "bitwasp/bitcoin-php"
}```

##Presently supported:

 - Blocks, block headers, basic mining, difficulty calculations
 - P2SH, Multisignature scripts.
 - ECDSA key creation, public & private key types. 
 - Transactions
 - Signature creation & verification 
 - Deterministic signatures (RFC6979)
 - BIP32 deterministic algorithms
 - Script builder, parser
 - RPC bindings to Bitcoin Core's RPC, getting OOP responses :)
 - Easy serialization to binary representation of most classes
 - SIGHASH types when creating transactions (not tested)
 - Payment Protocol (BIP70)

##Todo:
  - Full script interpreter
  - SPV
  - P2P
  
## Implemented BIPs
  - BIP0011
  - BIP0016
  - BIP0032
  - BIP0039
  - BIP0070
  
# Examples  
## Generate private keys
```php
 // Create private keys
 use BitWasp\Bitcoin\Key\PrivateKeyFactory;
 
 $network = Bitcoin::GetNetwork();
 $private = PrivateKeyFactory:create(true);
 $public = $private->getPublicKey();
 echo $private->getBuffer() . "\n";
 echo $public->getBuffer() . "\n";
 echo $public->getAddress($network) . "\n";
```

## Explore the blockchain using OOP bindings to the RPC
```php
use BitWasp\Bitcoin\Rpc\RpcFactory;

$bitcoind = RpcFactory::bitcoind('127.0.0.1', 18332, 'bitcoinrpc', 'BBpsLqmCCx7Vp8sRd5ygDxFkHZBgWLTTi55QwWgN6Ng6');

$hash = $bitcoind->getbestblockhash();
$block = $bitcoind->getblock($hash);
$tx = $bitcoind->getTransactions()->getTransaction(10);
echo $tx->getTransactionId();
```

## Create signed payment requests 
```php
use BitWasp\Bitcoin\Address\AddressFactory;
use BitWasp\Bitcoin\PaymentProtocol\PaymentRequestBuilder;
use BitWasp\Bitcoin\PaymentProtocol\PaymentRequestSigner;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Transaction\TransactionOutput;

$time = time();
$amount = 10000;
$destination = '18Ffckz8jsjU7YbhP9P44JMd33Hdkkojtc';
$paymentUrl = 'http://192.168.0.223:81/bitcoin-php/examples/bip70.fetch.php?time=' . $time;

// Create a signer for x509+sha256 - this requires a readable private key and certificate chain.
// $signer = new PaymentRequestSigner('none');
$signer = new PaymentRequestSigner('x509+sha256', '/var/www/git/paymentrequestold/.keys/ssl.key', '/var/www/git/paymentrequestold/.keys/ssl.pem');
$builder = new PaymentRequestBuilder($signer, 'main', time());

// PaymentRequests contain outputs that the wallet will fulfill
$address = AddressFactory::fromString($destination);
$builder->addAddressPayment($address, $amount);

// Create the request, send it + headers
$request = $builder->send();

```
