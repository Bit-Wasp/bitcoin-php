  
##Bitcoin
=======
[![Build Status](https://scrutinizer-ci.com/g/bit-wasp/bitcoin-php/badges/build.png?b=master)](https://scrutinizer-ci.com/g/bit-wasp/bitcoin-php/build-status/master)
[![Code Coverage](https://scrutinizer-ci.com/g/bit-wasp/bitcoin-php/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/bit-wasp/bitcoin-php/?branch=master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/Bit-Wasp/bitcoin-php/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/Bit-Wasp/bitcoin-php/?branch=master)

[![Latest Stable Version](https://poser.pugx.org/bitwasp/bitcoin/v/stable.png)](https://packagist.org/packages/bitwasp/bitcoin)
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
`composer require bitwasp/bitcoin`
or 
```{
    "require": "bitwasp/bitcoin"
}```

##Presently supported:

 - Blocks, block headers, basic mining, difficulty calculations
 - P2SH, Multisignature scripts.
 - ECDSA key creation, public & private key types. 
 - Transactions
 - Signature creation & verification 
 - Deterministic signatures (RFC6979)
 - BIP32 deterministic algorithms
 - Script builder for common input/output types, parser, interpreter.
 - RPC bindings to Bitcoin Core's RPC, getting OOP responses :)
 - Easy serialization to binary representation of most classes
 - SIGHASH types when creating transactions (not tested)
 - Payment Protocol (BIP70)

##Known Issues:
The script interpreter has a modest set of test vectors, but these are mostly positive tests, that don't really exercise many of the edge cases. While it works, it's not bug-for-bug compatible yet. 

##Todo:
  - SPV
  - P2P
  - Wishlist: 
     - libbitcoinconsensus as an extension, and an adapter similar to EcAdapter to allow switching between this + the pure PHP code. 
  
## Implemented BIPs
  - BIP0011
  - BIP0016
  - BIP0032
  - BIP0039
  - BIP0066
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
use BitWasp\Bitcoin\Script\Interpreter\Native\NativeInterpreter;
use BitWasp\Bitcoin\Script\Interpreter\Flags;
use BitWasp\Bitcoin\Transaction\Transaction;

$ec = \BitWasp\Bitcoin\Bitcoin::getEcAdapter();

$script = ScriptFactory::create()->op('OP_1')->op('OP_1')->op('OP_ADD');

echo "Formed script: " . $script . "\n";
print_r($script->getScriptParser()->parse());

$i = new NativeInterpreter($ec, new Transaction(), new Flags(0));
$i->setScript($script)->run();

print_r($i->getStackState());
```
