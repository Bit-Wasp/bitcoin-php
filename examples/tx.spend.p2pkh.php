<?php

require "../vendor/autoload.php";

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Key\PrivateKeyFactory;
use BitWasp\Bitcoin\Network\NetworkFactory;
use BitWasp\Bitcoin\Script\Interpreter\InterpreterInterface as I;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Transaction\Factory\Signer;
use BitWasp\Bitcoin\Transaction\Factory\TxBuilder;
use BitWasp\Bitcoin\Transaction\OutPoint;
use BitWasp\Bitcoin\Transaction\TransactionOutput;
use BitWasp\Buffertools\Buffer;

Bitcoin::setNetwork(NetworkFactory::bitcoinSegnet());
$key = PrivateKeyFactory::fromWif('QP3p9tRpTGTefG4a8jKoktSWC7Um8qzvt8wGKMxwWyW3KTNxMxN7');

// UTXO
$outpoint = new OutPoint(Buffer::hex('5664aa8f70c85cf0469f0b9450d24a114ba5d71ed1ccdecfcae8f84e53d56a5e', 32), 0);
$txOut = new TransactionOutput(99900000, ScriptFactory::scriptPubKey()->payToPubKeyHash($key->getPublicKey()));

// Create unsigned transaction
$tx = (new TxBuilder())
    ->spendOutPoint($outpoint)
    ->payToAddress(99850000, $key->getPublicKey()->getAddress())
    ->get();

// Sign transaction
$signed = (new Signer($tx, Bitcoin::getEcAdapter()))
    ->sign(0, $key, $txOut)
    ->get();

// Check our signature is correct
$consensus = ScriptFactory::consensus(I::VERIFY_P2SH | I::VERIFY_WITNESS);
echo "Script validation result: " . ($signed->validator()->checkSignature($consensus, 0, $txOut) ? "yay\n" : "nay\n");

echo PHP_EOL;
echo "Base serialized transaction: " . $signed->getHex() . PHP_EOL;