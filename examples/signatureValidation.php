<?php

require "../vendor/autoload.php";

use BitWasp\Buffertools\Buffer;
use BitWasp\Bitcoin\Crypto\Hash;
use BitWasp\Bitcoin\Key\PrivateKeyFactory;
use BitWasp\Bitcoin\Transaction\TransactionFactory;
use BitWasp\Bitcoin\Script\ScriptFactory;

$ent = Hash::sha256(new Buffer('abc'));
$priv = PrivateKeyFactory::fromHex($ent);
$publicKey = $priv->getPublicKey();
$outputScript = ScriptFactory::scriptPubKey()->payToPubKeyHash($publicKey);

$tx = TransactionFactory::build()
    ->input('10001234abcd1234abcd1234abcd1234abcd1234abcd1234abcd1234abcd1234', 0)
    ->payToAddress(50, $publicKey->getAddress())
    ->get();

$signed = (new \BitWasp\Bitcoin\Transaction\Factory\Signer($tx, \BitWasp\Bitcoin\Bitcoin::getEcAdapter()))
    ->sign(0, $priv, $tx->getOutput(0))
    ->get();

echo $signed->getHex();

$flags = \BitWasp\Bitcoin\Script\Interpreter\InterpreterInterface::VERIFY_NONE;
$consensus = ScriptFactory::consensus($flags);
$validator = $signed->validator();

var_dump($validator->checkSignatures($consensus, [$outputScript]));
