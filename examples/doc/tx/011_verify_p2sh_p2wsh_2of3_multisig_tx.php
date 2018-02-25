<?php

use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Transaction\Factory\Signer;
use BitWasp\Bitcoin\Transaction\TransactionFactory;
use BitWasp\Bitcoin\Transaction\TransactionOutput;

require __DIR__ . "/../../../vendor/autoload.php";

/**
 * This example shows how a fully signed transaction
 * can be verified with only it's scriptPubKey using
 * the signer instance.
 *
 * You should notice that unlike the other sign examples
 * where the tx was unsigned before the Signer was created,
 * this time we didn't have to pass the SignData. The Signer
 * determines the redeemScript and witnessScript are necessary
 * and finds them.
 *
 * This example is using the default script verification
 * flags, set in Signer. It's probably better than trying
 * to manage them yourself.
 */

$txOut = new TransactionOutput(
    500000000,
    ScriptFactory::fromHex('a914521ab4907bf71c5113954be128931f0f32d48a8d87')
);

$tx = TransactionFactory::fromHex('01000000000101a14e1eade25cba0cc1a6070178dc17f35ff6f9e33f8df517982e139d63b7f7870000000023220020ec5ac8d7b8f349b39ffadde8b4955b9055184805f723d0b277144cc0d7c152e5ffffffff0200c2eb0b000000001976a91488ed05abdbc1f46d1e6b3f482cae3965e9679d5888ac800c49110000000017a914521ab4907bf71c5113954be128931f0f32d48a8d870400483045022100bd61130e6560fc6e6544ed76c3225219f1956e12e8049391e68a2aac9444a0bf02201327d7521708079d1842ad3b3e97618ea240002b1fcfaf7c376c07ba7a23d1430147304402203f3e7e148f741064ecb3571c077ce63cf948618ff96d6afdb760e5bb81ccedde0220393588b9e8a2db3a157a9643217178628279b1c17ba39a882d84c0d4016107e501695221020a48636b5df07a3f8fbcb9c3d0af1dbe2c6c8250dd7a66c006c44d106acc0a72210299e766c1a8e6ab5c4f19600b694e98fdda521daedb0973a894f0f2811f6b50772103fff6dc247b15006cb88ad4d052f303e063ac88e99c3eb98b2d20aa9328943cd953ae00000000');

$signer = new Signer($tx);
$input = $signer->input(0, $txOut);
var_dump($input->verify());
