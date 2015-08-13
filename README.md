  
##Bitcoin
=======
[![Build Status](https://scrutinizer-ci.com/g/bit-wasp/bitcoin-php/badges/build.png?b=master)](https://scrutinizer-ci.com/g/bit-wasp/bitcoin-php/build-status/master)
[![Code Coverage](https://scrutinizer-ci.com/g/bit-wasp/bitcoin-php/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/bit-wasp/bitcoin-php/?branch=master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/Bit-Wasp/bitcoin-php/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/Bit-Wasp/bitcoin-php/?branch=master)

[![Latest Stable Version](https://poser.pugx.org/bitwasp/bitcoin/v/stable.png)](https://packagist.org/packages/bitwasp/bitcoin)
[![Join the chat at https://gitter.im/Bit-Wasp/bitcoin-php](https://badges.gitter.im/Join%20Chat.svg)](https://gitter.im/Bit-Wasp/bitcoin-php?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge&utm_content=badge)

  This repository contains an implementation of Bitcoin using mostly pure PHP.

  As the saying goes - "never use a dynamically interpreted language to write consensus software"
  Now that you know this, please continue!

  If the relevant extensions are installed, ie, secp256k1-php and bitcoinconsensus-php,
  it will be used instead of the native PHP implementation.

  Besides classes for interfacing with Bitcoind's RPC, and Stratum servers, this
  library does NOT contain any code to interact with the network, or any API's.

  Other repositories which are part of this project:
    [Secp256k1 PHP extension](https://github.com/Bit-Wasp/secp256k1-php)
    [Bitcoinconsensus PHP extension](https://github.com/Bit-Wasp/bitcoinconsensus-php)
    [Buffertools library](https://github.com/Bit-Wasp/buffertools-php) - a library for dealing with binary data
    [Stratum client library](https://github.com/Bit-Wasp/stratum-php) - a library for connecting to Stratum servers (uses ReactPHP)
    [Bitcoin P2P PHP](https://github.com/Bit-Wasp/bitcoin-p2p-php) - a library that speaks the Bitcoin protocol (uses ReactPHP)
    [Testing package](https://github.com/Bit-Wasp/testing-php) - a composer package to pull CI and development tools

## Requirements:
 * PHP 5.4+
 * Composer
 * ext-gmp
 * ext-mcrypt

## Optional:
 * [[secp256k1-php](https://github.com/Bit-Wasp/secp256k1-php)] - Install the secp256k1 PHP extension for blazing speeds.
 * [[bitcoinconsensus-php](https://github.com/Bit-Wasp/bitcoin-consensus-php)] - Install libbitcoinconsensus for script validation

## Installation
You can install this library via Composer:
`composer require bitwasp/bitcoin`
or
```{
    "require": "bitwasp/bitcoin"
}```


## Contributing
  Contributions are most welcome, and the more eyes on the code the better.

  To get started:
   - Fork this library
   - Check out the code:
     `git clone git@github.com:yourfork/bitcoin-php && cd bitcoin-php`
   - Start your own branch:
     `git checkout -b your-feature-branch`
   - Check your work, and apply code style:
       (phing): `phing`
       (other) `vendor/bin/phpunit && vendor/bin/phpcbf -n --standard=PSR1,PSR2 tests src`
   - Commit your work:
      `git commit -a`
   - Push your work:
      `git push origin your-feature-branch`
   - And open a pull request!

  There will always be some iteration over new features - mainly this is to ensure
  classes don't run afoul of scope creep, and that the library remains precise and powerful.

  Please supplement all submissions with tests, and ensure it conforms with the code style.

##Presently supported:

 - Bloom filters
 - Blocks, headers, and merkle blocks.
 - Regular/P2SH scripts.
 - An adaptable elliptic-curve library, using mdanter/phpecc by default, or libsecp256k1 if the bindings are found.
 - Support for building, parsing, signing/validating transactions.
 - Deterministic signatures (RFC6979)
 - BIP32 and electrum (older type I) deterministic key algorithms.
 - ScriptFactory for common input/output types, parser, interpreter, and classifiers.
 - Supports bindings to libbitcoinconsensus.
 - RPC bindings to Bitcoin Core's RPC, getting OOP responses
 - Bindings to Stratum (electrum) servers
 - Easy serialization to binary representation of most classes
 - SIGHASH types when creating transactions (not tested)
 - Payment Protocol (BIP70)
 - Blockchain classes utilizing the doctrine/cache package

##Known Issues:

  The script interpreter has a modest set of test vectors, but these are mostly positive tests, that don't really exercise many of the edge cases. While it works, it's not bug-for-bug compatible yet and should not be relied on for consensus.
  Similarly, the secp256k1-php extension is a wrapper around an experimental library which has not yet been formally released. It's use should not be relied on until the upstream library has made a stable release. 

## Implemented BIPs

  - BIP0011 - M of N standard transactions
  - BIP0016 / BIP0013 - Pay to Script hash, and corresponding address format.
  - BIP0014 - Protocol Version and User Agent
  - BIP0031 - Pong message
  - BIP0032 - Hierarchical Deterministic Wallets
  - BIP0035 - Mempool Message
  - BIP0037 - Blooom Filtering
  - BIP0039 - Mnemonic code for generating determinisitc keys
  - BIP0066 - Strict DER Signatures
  - BIP0067 - Deterministic P2SH multi-signature addresses
  - BIP0070 - Payment Protocol
  
# Examples  
## Generate private keys
```php
 // Create private keys
 use BitWasp\Bitcoin\Bitcoin;
 use BitWasp\Bitcoin\Key\PrivateKeyFactory;

 $network = Bitcoin::getNetwork();
 $private = PrivateKeyFactory::create(true);
 $public = $private->getPublicKey();
 echo $private->getHex() . "\n";
 echo $private->toWif($network) . "\n";
 echo $public->getHex() . "\n";
 echo $public->getAddress($network)->getAddress() . "\n";

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

## Sign multi-signature transactions
```php

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Key\PrivateKeyFactory;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Transaction\Transaction;
use BitWasp\Bitcoin\Transaction\TransactionInput;
use BitWasp\Bitcoin\Transaction\TransactionOutput;
use BitWasp\Bitcoin\Transaction\TransactionBuilder;
use BitWasp\Bitcoin\Transaction\TransactionFactory;

$ecAdapter = Bitcoin::getEcAdapter();

// Two users independently create private keys.
$pk1 = PrivateKeyFactory::fromHex('421c76d77563afa1914846b010bd164f395bd34c2102e5e99e0cb9cf173c1d87');
$pk2 = PrivateKeyFactory::fromHex('f7225388c1d69d57e6251c9fda50cbbf9e05131e5adb81e5aa0422402f048162');

// They exchange public keys, and a multisignature address is made (sorted keys)
$redeemScript = ScriptFactory::multisig(2, [$pk1->getPublicKey(), $pk2->getPublicKey()], true);
$outputScript = $redeemScript->getOutputScript();

// The address is funded with a transaction (fake, for the purposes of this script).
// You would do getrawtransaction normally
$spendTx = new Transaction();
$spendTx->getInputs()->addInput(new TransactionInput(
    '4141414141414141414141414141414141414141414141414141414141414141',
    0
));
$spendTx->getOutputs()->addOutput(new TransactionOutput(
    50,
    $outputScript
));

// One party wants to spend funds. He creates a transaction spending the funding tx to his address.
$builder = new TransactionBuilder($ecAdapter);
$builder->spendOutput($spendTx, 0)
    ->payToAddress($pk1->getAddress(), 50)
    ->signInputWithKey($pk1, $outputScript, 0, $redeemScript)
    ->signInputWithKey($pk2, $outputScript, 0, $redeemScript);

$rawTx = $builder->getTransaction()->getHex();

echo "Fully signed transaction: " . $builder->getTransaction()->getHex() . "\n";

```

## Create/verify signed Bitcoin messages
```php

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\MessageSigner\MessageSigner;
use BitWasp\Bitcoin\Key\PrivateKeyFactory;

$ec = Bitcoin::getEcAdapter();
$privateKey = PrivateKeyFactory::create(true);

$message = 'hi';

$signer = new MessageSigner($ec);
$signed = $signer->sign($message, $privateKey);

echo sprintf("Signed by %s\n%s\n", $privateKey->getAddress()->getAddress(), $signed->getBuffer()->getBinary());

if ($signer->verify($signed, $privateKey->getAddress())) {
    echo "Signature verified!\n";
} else {
    echo "Failed to verify signature!\n";
}

```

## Create/use BIP39 mnemonics
```php

use BitWasp\Bitcoin\Crypto\Random\Random;
use BitWasp\Bitcoin\Mnemonic\MnemonicFactory;
use BitWasp\Bitcoin\Mnemonic\Bip39\Bip39SeedGenerator;

// Generate a mnemonic
$random = new Random();
$entropy = $random->bytes(64);

$bip39 = MnemonicFactory::bip39();
$seedGenerator = new Bip39SeedGenerator($bip39);
$mnemonic = $bip39->entropyToMnemonic($entropy);

// Derive a seed from mnemonic/password
$seed = $seedGenerator->getSeed($mnemonic, 'password');
echo $seed->getHex() . "\n";

$bip32 = \BitWasp\Bitcoin\Key\HierarchicalKeyFactory::fromEntropy($seed);

```

## Write/interpret bitcoin scripts
```php
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Script\Interpreter\Native\Interpreter;
use BitWasp\Bitcoin\Script\Interpreter\Flags;
use BitWasp\Bitcoin\Transaction\Transaction;

$ec = \BitWasp\Bitcoin\Bitcoin::getEcAdapter();

$script = ScriptFactory::create()->op('OP_1')->op('OP_1')->op('OP_ADD');

echo "Formed script: " . $script . "\n";
print_r($script->getScriptParser()->parse());

$i = new Interpreter($ec, new Transaction(), new Flags(0));
$i->setScript($script)->run();

print_r($i->getStackState());
```
