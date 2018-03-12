<?php

use BitWasp\Bitcoin\Key\Factory\PrivateKeyFactory;
use BitWasp\Bitcoin\Network\NetworkFactory;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Transaction\Factory\Signer;
use BitWasp\Bitcoin\Transaction\SignatureHash\SigHash;
use BitWasp\Bitcoin\Transaction\TransactionFactory;
use BitWasp\Bitcoin\Transaction\TransactionOutput;

require __DIR__ . "/../vendor/autoload.php";

$txHex = "01000000037db7f0b2a345ded6ddf28da3211a7d7a95a2943e9a879493d6481b7d69613f04010000006b483045022100e822f152bb15a1d623b91913cd0fb915e9f85a8dc6c26d51948208bbc0218e800220255f78549d9614c88eac9551429bc00224f22cdcb41a3af70d52138f7e98d333032102f1c7eac9200f8dee7e34e59318ff2076c8b3e3ac7f43121e57569a1aec1803d4ffffffff652c491e5a781a6a3c547fa8d980741acbe4623ae52907278f10e1f064f67e05000000006a47304402206f37f79adeb86e0e2da679f79ff5c3ba206c6d35cd9a21433f0de34ee83ddbc00220118cabbac5d83b3aa4c2dc01b061e4b2fe83750d85a72ae6a1752300ee5d9aff032102f1c7eac9200f8dee7e34e59318ff2076c8b3e3ac7f43121e57569a1aec1803d4ffffffffb9fa270fa3e4dd8c79f9cbfe5f1953cba071ed081f7c277a49c33466c695db35000000006a473044022019a2a3322dcdb0e0c25df9f03f264f2c88f43b3b648fec7a28cb85620393a9750220135ff3a6668c6d6c05f32069e47a1feda10979935af2470c97fcb388f96f9738032102f1c7eac9200f8dee7e34e59318ff2076c8b3e3ac7f43121e57569a1aec1803d4ffffffff02204e0000000000001976a9149ed1f577c60e4be1dbf35318ec12f51d25e8577388ac30750000000000001976a914fb407e88c48921d5547d899e18a7c0a36919f54d88ac00000000";
$tx = TransactionFactory::fromHex($txHex);

$privFactory = PrivateKeyFactory::compressed();
$privKey = $privFactory->fromWif('cQnFidqYxEoi8xZz1hDtFRcEkzpXF5tbofpWbgWdEk9KHhAo7RxD', NetworkFactory::bitcoinTestnet());

$signer = new Signer($tx);

$txOut1 = new TransactionOutput(40000, ScriptFactory::fromHex("76a9140de1f9b92d2ab6d8ead83f9a0ff5cf518dcb03b888ac"));
$txOut2 = new TransactionOutput(40000, ScriptFactory::fromHex("76a9140de1f9b92d2ab6d8ead83f9a0ff5cf518dcb03b888ac"));
$txOut3 = new TransactionOutput(40000, ScriptFactory::fromHex("76a9140de1f9b92d2ab6d8ead83f9a0ff5cf518dcb03b888ac"));

$sigHash = SigHash::SINGLE;

$input1 = $signer->input(0, $txOut1);
echo $input1->getSigHash(SigHash::SINGLE)->getHex().PHP_EOL;
$input1->sign($privKey);

$input2 = $signer->input(0, $txOut2);
echo $input2->getSigHash(SigHash::SINGLE)->getHex().PHP_EOL;
$signer->sign(1, $privKey, $txOut2);

$input2 = $signer->input(2, $txOut2);
echo $input2->getSigHash(SigHash::SINGLE)->getHex().PHP_EOL;
$input2->sign($privKey);

$signed = $signer->get();
echo $signed->getHex().PHP_EOL;
