# Transactions

 The library has classes for the transaction primitives in Bitcoin:
 
## Structures

### OutPoint
 OutPoints wrap a Buffer of the transaction ID, and the vout index that is to be spent. 
```php
use BitWasp\Buffertools\Buffer;
use BitWasp\Bitcoin\Transaction\OutPoint;

$txid = Buffer::hex('87f7b7639d132e9817f58d3fe3f9f65ff317dc780107a6c10cba5ce2ad1e4ea1');
$vout = 0;
$outpoint = new OutPoint($txid, $vout);
```
 
### TransactionInput
 TransactionInputs consist of an OutPoint, the scriptSig, and a sequence number. 
 The $script has a default value of null, where an empty script will be used.  
 The $sequence has a default value of TransactionInput::SEQUENCE_FINAL (0xffffffff)
 
```php
use BitWasp\Buffertools\Buffer;
use BitWasp\Bitcoin\Transaction\OutPoint;
use BitWasp\Bitcoin\Script\Script;
use BitWasp\Bitcoin\Transaction\TransactionInput;

$txid = Buffer::hex('87f7b7639d132e9817f58d3fe3f9f65ff317dc780107a6c10cba5ce2ad1e4ea1');
$vout = 0;
$outpoint = new OutPoint($txid, $vout);

$sequence = TransactionInput::SEQUENCE_FINAL;
$script = new Script();
$input = new TransactionInput($outpoint, $script, $sequence);
```

The TransactionInput class derives it's methods from TransactionInputInterface:
[../src/Transaction/TransactionInputInterface.php]

### TransactionOutput
```php
use BitWasp\Bitcoin\Script\Script;
use BitWasp\Bitcoin\Transaction\TransactionOutput;

$value = 100000000; /* amounts are satoshis */
$script = new Script();
$output = new TransactionOutput($value, $script);
```

The TransactionOutput class derives it's methods from TransactionOutputInterface:
[../src/Transaction/TransactionOutputInterface.php]

### Transaction
```php
use BitWasp\Buffertools\Buffer;
use BitWasp\Bitcoin\Script\Script;
use BitWasp\Bitcoin\Transaction\OutPoint;
use BitWasp\Bitcoin\Transaction\Transaction;
use BitWasp\Bitcoin\Transaction\TransactionInput;
use BitWasp\Bitcoin\Transaction\TransactionOutput;

$txid = Buffer::hex('87f7b7639d132e9817f58d3fe3f9f65ff317dc780107a6c10cba5ce2ad1e4ea1');
$vout = 0;
$outpoint = new OutPoint($txid, $vout);
$input = new TransactionInput($outpoint, new Script());

$value = 100000000; /* amounts are satoshis */
$scriptPubKey = new Script();
$output = new TransactionOutput($value, $scriptPubKey);

$transaction = new Transaction(
    1, /* version */
    [$input], /* vTxIn */
    [$output], /* vTxOut */
    [], /* vWit */
    0 /* locktime */
);
```

## Transaction Builder

 The library has a simplified interface for creating transactions. The TransactionBuilder
 is used to create unsigned transactions, or simply to create a transaction from an API response. 
 
 An input can be added to the transaction using:
 
   `TxBuilder::input()` - providing the txid string, and vout index, along with a script (default is empty), and sequence (default is MAX)
   
   `TxBuilder::inputs()` - by providing an array of `TransactionInputInterface`
   
   `TxBuilder::spendOutPoint()` - which takes an OutPoint, and optionally a script and sequence.
   
   `TxBuilder::spendOutPointFrom()` - which takes a transaction, and a vout index, and optionally a script and sequence
  
 An output can be added using:
  `TxBuilder::output()` - providing a value, and a script.
  `TxBuilder::payToAddress()` - providing a value, and an AddressInterface
  `TxBuilder::outputs()` - providing an array of `TransactionOutputInterface`
  
 Witness data can be added using:
  `TxBuilder::witnesses()` - providing an array of `ScriptWitnessInterface` 
  
```php
use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Transaction\TransactionFactory;
use BitWasp\Bitcoin\Script\Script;
use BitWasp\Bitcoin\Locktime;

$math = Bitcoin::getMath();
$lockTime = new Locktime($math);
$scriptPubKey = new Script();

$transaction = TransactionFactory::build()
    ->version(1)
    ->input('abcdb7639d132e9817f58d3fe3f9f65ff317dc780107a6c10cba5ce2ad1eabcd', 0)
    ->output(1500000, $scriptPubKey)
    ->payToAddress(1500000, \BitWasp\Bitcoin\Address\AddressFactory::fromString('1DUzqgG31FvNubNL6N1FVdzPbKYWZG2Mb6'))
    ->lockToBlockHeight($lockTime, 400000)
    ->get();
```
 
## Transaction Signer

 Transactions can be signed using `BitWasp\Bitcoin\Transaction\Factory\Signer`
 
 This class handles P2SH and witness transactions or plain output scripts, but only
 if the actual script type is a pay-to-pubkey, pay-to-pubkey-hash, or multisig script. 
 
 The transaction to-be-signed must be passed via the constructor. Signatures will be extracted wherever a full set is found.
  
 Sign is the only method in this class. `$nInput`, `$key`, and `$txOut` are always required. 
 Signing should be viewed as a generic operation, where a known scriptPubKey is solved by a key. 
 In all cases, the key and output script are provided. 
  
 Signing a plain output script (including Witness V0 KeyHash) will not require any additional parameters. 
 Signing a Witness V0 ScriptHash: the witness script must be provided 
 Signing a P2SH output: the redeem script must be provided
 P2SH and Witness scripts can be used together, in which case, the witness & redeem script must both be provided. 
 
 It should be noted that a transaction can *always* be validated, but signing requires code specific to the script type. 
 While the above listed types are supported, others will require modification to the InputSigner.
 
 InputSigners are managed by the Signer, but they have some important responsibilities:
  - Extracting signatures for supported types
  - Signing supported types
  - Re-serializing the scriptSig and scriptWitness fields
 
### Simple output script: pay to pubkey hash
```php
use BitWasp\Buffertools\Buffer;
use BitWasp\Bitcoin\Transaction\OutPoint;
use BitWasp\Bitcoin\Transaction\TransactionOutput;
use BitWasp\Bitcoin\Transaction\TransactionFactory;
use BitWasp\Bitcoin\Transaction\Factory\Signer;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Key\PrivateKeyFactory;

$privateKey = PrivateKeyFactory::fromWif('5Hwig3iZrm6uxS6Ch1egmJGyC89Q76X5tgVgtbEcLTPTx3aW5Zi');

// Utxo is: outpoint.txid, outpoint.vout, txout.scriptPubKey, txout.amount
$outpoint = new OutPoint(Buffer::hex('87f7b7639d132e9817f58d3fe3f9f65ff317dc780107a6c10cba5ce2ad1e4ea1'), 0);
$outputScript = ScriptFactory::scriptPubKey()->payToPubKeyHash($privateKey->getPubKeyHash());
$amount = 1501000;
$txOut = new TransactionOutput($amount, $outputScript);

$transaction = TransactionFactory::build()
    ->spendOutPoint($outpoint)
    ->payToAddress(1500000, \BitWasp\Bitcoin\Address\AddressFactory::fromString('1DUzqgG31FvNubNL6N1FVdzPbKYWZG2Mb6'))
    ->get();

$ec = \BitWasp\Bitcoin\Bitcoin::getEcAdapter();
$signed = (new Signer($transaction, $ec))
    ->sign(0, $privateKey, $txOut)
    ->get();
```

### P2SH: 1 of 2 multisig
```php
$privateKey = PrivateKeyFactory::fromWif('5Hwig3iZrm6uxS6Ch1egmJGyC89Q76X5tgVgtbEcLTPTx3aW5Zi');
$privateKey2 = PrivateKeyFactory::create();

// Utxo is: outpoint.txid, outpoint.vout, txout.scriptPubKey, txout.amount
$outpoint = new OutPoint(Buffer::hex('87f7b7639d132e9817f58d3fe3f9f65ff317dc780107a6c10cba5ce2ad1e4ea1'), 0);

$redeemScript = ScriptFactory::p2sh()->multisig(1, [$privateKey->getPublicKey(), $privateKey2->getPublicKey()]);
$outputScript = $redeemScript->getOutputScript();

$amount = 1501000;
$txOut = new TransactionOutput($amount, $outputScript);

$transaction = TransactionFactory::build()
    ->spendOutPoint($outpoint)
    ->payToAddress(1500000, \BitWasp\Bitcoin\Address\AddressFactory::fromString('1DUzqgG31FvNubNL6N1FVdzPbKYWZG2Mb6'))
    ->get();

$ec = \BitWasp\Bitcoin\Bitcoin::getEcAdapter();
$signed = (new Signer($transaction, $ec))
    ->sign(0, $privateKey, $txOut, $redeemScript)
    ->get();
```

### Witness V0 ScriptHash: 1 of 2 multisig
```php
use BitWasp\Bitcoin\Transaction\Factory\Signer;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Transaction\OutPoint;
use BitWasp\Buffertools\Buffer;
use BitWasp\Bitcoin\Transaction\TransactionOutput;
use BitWasp\Bitcoin\Transaction\TransactionFactory;
use BitWasp\Bitcoin\Key\PrivateKeyFactory;
use BitWasp\Bitcoin\Crypto\Hash;
use BitWasp\Bitcoin\Script\WitnessProgram;

$privateKey = PrivateKeyFactory::fromWif('5Hwig3iZrm6uxS6Ch1egmJGyC89Q76X5tgVgtbEcLTPTx3aW5Zi');
$privateKey2 = PrivateKeyFactory::create();

// Utxo is: outpoint.txid, outpoint.vout, txout.scriptPubKey, txout.amount
$outpoint = new OutPoint(Buffer::hex('87f7b7639d132e9817f58d3fe3f9f65ff317dc780107a6c10cba5ce2ad1e4ea1'), 0);

$witnessScript = ScriptFactory::scriptPubKey()->multisig(1, [$privateKey->getPublicKey(), $privateKey2->getPublicKey()]);
$witnessProgram = new WitnessProgram(0, Hash::sha256($witnessScript->getBuffer()));
$outputScript = $witnessProgram->getScript();

$amount = 1501000;
$txOut = new TransactionOutput($amount, $outputScript);

$transaction = TransactionFactory::build()
    ->spendOutPoint($outpoint)
    ->payToAddress(1500000, \BitWasp\Bitcoin\Address\AddressFactory::fromString('1DUzqgG31FvNubNL6N1FVdzPbKYWZG2Mb6'))
    ->get();

$ec = \BitWasp\Bitcoin\Bitcoin::getEcAdapter();
$signed = (new Signer($transaction, $ec))
    ->sign(0, $privateKey, $txOut, null, $witnessScript)
    ->get();

echo "Get non-witness transaction " . $signed->getBuffer()->getHex() . PHP_EOL . PHP_EOL;
echo "Get witness bearing transaction: " . $signed->getWitnessBuffer()->getHex() . PHP_EOL;
```


### P2SH | Witness V0 Script Hash: 1 of 2 multisig
```php

use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Transaction\OutPoint;
use BitWasp\Buffertools\Buffer;
use BitWasp\Bitcoin\Transaction\TransactionOutput;
use BitWasp\Bitcoin\Transaction\TransactionFactory;
use BitWasp\Bitcoin\Key\PrivateKeyFactory;

$privateKey = PrivateKeyFactory::fromWif('5Hwig3iZrm6uxS6Ch1egmJGyC89Q76X5tgVgtbEcLTPTx3aW5Zi');
$privateKey2 = PrivateKeyFactory::fromHex('1a0733d2c0cde1ec521bee36f16775eb3e5e368431484eeb02afe72499c5865f');

$outpoint = new OutPoint(Buffer::hex('87f7b7639d132e9817f58d3fe3f9f65ff317dc780107a6c10cba5ce2ad1e4ea1'), 0);
$multisig = ScriptFactory::scriptPubKey()->multisig(1, [$privateKey->getPublicKey(), $privateKey2->getPublicKey()], false);
$witnessProgram = new \BitWasp\Bitcoin\Script\WitnessProgram(0, \BitWasp\Bitcoin\Crypto\Hash::sha256($multisig->getBuffer()));
$p2shOutScript = ScriptFactory::scriptPubKey()->payToScriptHash($witnessProgram->getScript());


$amount = 1501000;
$txOut = new TransactionOutput($amount, $p2shOutScript);

$transaction = TransactionFactory::build()
    ->spendOutPoint($outpoint)
    ->payToAddress(1500000, \BitWasp\Bitcoin\Address\AddressFactory::fromString('1DUzqgG31FvNubNL6N1FVdzPbKYWZG2Mb6'))
    ->get();

$ec = \BitWasp\Bitcoin\Bitcoin::getEcAdapter();
$signed = (new \BitWasp\Bitcoin\Transaction\Factory\Signer($transaction, $ec))
    ->sign(0, $privateKey, $txOut, $witnessProgram->getScript(), $multisig)
    ->get();

echo "Witness bearing transaction " . $signed->getWitnessBuffer()->getHex() . PHP_EOL. PHP_EOL;
echo "Non-witness transaction " . $signed->getBuffer()->getHex() . PHP_EOL;

```


## Checking Signatures

Signed transactions can be checked, so long as the txOut is known. For non-witness transactions, the amount does not have to be known, but it's better to keep it available.  

Checking signatures is done using the script interpreter. Since there exist multiple bindings to choose from, `ScriptFactory::consensus()`
will always return the most suitable. 

The example below validates a transaction produced in the `P2SH | Witness V0 Script Hash: 1 of 2 multisig` example.

### Checking a P2SH | witness v0 script hash: 1 of 2 multisig
```php

use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Transaction\OutPoint;
use BitWasp\Buffertools\Buffer;
use BitWasp\Bitcoin\Transaction\TransactionOutput;
use BitWasp\Bitcoin\Transaction\TransactionFactory;

$outpoint = new OutPoint(Buffer::hex('87f7b7639d132e9817f58d3fe3f9f65ff317dc780107a6c10cba5ce2ad1e4ea1'), 0);
$multisig = ScriptFactory::fromHex('5141047695c66e05a2b57dac3cbd84ff9d65f35d110cf0c5229811a952fe94f039480c8d10942adfd39d2597ba394380b05e78034cf1a385a388cb38fad8a45cf180b34104edfd1a26415f325d999bc17373a6f91072c88883d5cf045087bac80dd8c2ce2adfd154dadc1b8996941e2f0f0adc5837b2acc94915547e33814d785a18e81a7652ae');
$witnessProgram = new \BitWasp\Bitcoin\Script\WitnessProgram(0, \BitWasp\Bitcoin\Crypto\Hash::sha256($multisig->getBuffer()));
$p2shOutScript = ScriptFactory::scriptPubKey()->payToScriptHash($witnessProgram->getScript());

$amount = 1501000;
$txOut = new TransactionOutput($amount, $p2shOutScript);

$parsed = TransactionFactory::fromHex('01000000000101a14e1eade25cba0cc1a6070178dc17f35ff6f9e33f8df517982e139d63b7f78700000000232200205aa8b7caaf9b2bfa6d9f9fb3bc6da6aa37fe66b56647962f5375d448796e7b18ffffffff0160e31600000000001976a91488ed05abdbc1f46d1e6b3f482cae3965e9679d5888ac030047304402205c2aed1832621e4cfb023789b9ed43e7787479590652f4422e8d3e1f9643e98f022070fddab960b5e30b6b70ba9c6c31263e4d7303eb4046bd816da08cc574fd817801875141047695c66e05a2b57dac3cbd84ff9d65f35d110cf0c5229811a952fe94f039480c8d10942adfd39d2597ba394380b05e78034cf1a385a388cb38fad8a45cf180b34104edfd1a26415f325d999bc17373a6f91072c88883d5cf045087bac80dd8c2ce2adfd154dadc1b8996941e2f0f0adc5837b2acc94915547e33814d785a18e81a7652ae00000000');
$consensus = ScriptFactory::consensus();
$r = $parsed->validator()->checkSignature($consensus, 0, $txOut);
var_dump($r);

```


