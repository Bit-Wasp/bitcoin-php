<?php

namespace BitWasp\Bitcoin\Tests\Script;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Crypto\EcAdapter\EcAdapterFactory;
use BitWasp\Bitcoin\Crypto\EcAdapter\EcAdapterInterface;
use BitWasp\Bitcoin\Key\PrivateKeyFactory;
use BitWasp\Bitcoin\Key\PrivateKeyInterface;
use BitWasp\Bitcoin\Math\Math;
use BitWasp\Bitcoin\Script\RedeemScript;
use BitWasp\Bitcoin\Script\Script;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Bitcoin\Script\ScriptInterpreter;
use BitWasp\Bitcoin\Transaction\Transaction;
use BitWasp\Bitcoin\Script\ScriptInterpreterFlags;
use BitWasp\Bitcoin\Transaction\TransactionBuilder;
use BitWasp\Bitcoin\Transaction\TransactionOutput;
use BitWasp\Bitcoin\Utxo\Utxo;
use BitWasp\Buffertools\Buffer;
use Mdanter\Ecc\EccFactory;

class ScriptInterpreterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param $flagStr
     * @return ScriptInterpreterFlags
     */
    private function setFlags($flagStr)
    {
        $array = explode(",", $flagStr);
        $int = 0;
        $checkdisabled = false;
        foreach ($array as $activeFlag) {
            if ($activeFlag == 'checkDisabledOpcodes') {
                $checkdisabled = true;
                continue;
            }

            $f = constant('\BitWasp\Bitcoin\Script\ScriptInterpreterFlags::'.$activeFlag);
            $int |= $f;
        }

        return new ScriptInterpreterFlags($int, $checkdisabled);
    }

    /**
     * Construct general test vectors to exercise Checksig operators.
     * Given an output script (limited to those signable by TransactionBuilder), RedeemScript if required,
     * and private key, the tests can attempt to spend a fake transaction. the scriptSig it produces should
     * return true against the given outputScript/redeemScript
     *
     * @return array
     */
    public function ChecksigVectors()
    {
        $ec = EcAdapterFactory::getAdapter(new Math(), EccFactory::getSecgCurves()->generator256k1());
        $privateKey = PrivateKeyFactory::fromHex('4141414141414141414141414141414141414141414141414141414141414141', false, $ec);

        $standard = ScriptInterpreterFlags::defaults();
        $vectors = [];

        // Pay to pubkey hash that succeeds
        $s0 = ScriptFactory::scriptPubKey()->payToPubKeyHash($privateKey->getPublicKey());
        $vectors[] = [
            true,
            $ec,
            $standard, // flags
            $privateKey,        // privKey
            $s0,
            null,               // redeemscript,
        ];

        // Pay to pubkey that succeeds
        $s1 = ScriptFactory::create()->push($privateKey->getPublicKey()->getBuffer())->op('OP_CHECKSIG');
        $vectors[] = [
            true,
            $ec,
            $standard, // flags
            $privateKey,        // privKey
            $s1,
            null,               // redeemscript
        ];

        $rs = ScriptFactory::multisig(1, [$privateKey->getPublicKey()]);
        $vectors[] = [
            true,
            $ec,
            $standard,
            $privateKey,
            $rs->getOutputScript(),
            $rs,
        ];

        return $vectors;
    }

    /**
     * @dataProvider ChecksigVectors
     */
    public function testChecksigVectors($eVerifyResult, EcAdapterInterface $ec, ScriptInterpreterFlags $flags, PrivateKeyInterface $privateKey, ScriptInterface $outputScript, RedeemScript $rs = null)
    {
        // Create a fake tx to spend - an output script we supposedly can spend.
        $fake = new TransactionBuilder($ec);
        $fake->addOutput(new TransactionOutput(1, $outputScript));

        // Here is where
        $spend = new TransactionBuilder($ec);
        $spend->spendOutput($fake->getTransaction(), 0);
        $spend->signInputWithKey($privateKey, $outputScript, 0, $rs);

        $spendTx = $spend->getTransaction();
        $scriptSig = $spendTx->getInputs()->getInput(0)->getScript();

        $i = new ScriptInterpreter($ec, $spendTx, $flags);
        $this->assertEquals($eVerifyResult, $i->verify($scriptSig, $outputScript, 0));
    }

    public function getScripts()
    {
        $f = file_get_contents(__DIR__ . '/../Data/scriptinterpreter.simple.json');
        $json = json_decode($f);

        $vectors = [];
        foreach ($json->test as $c => $test) {
            $flags = $this->setFlags($test->flags);
            $scriptSig = ScriptFactory::fromHex($test->scriptSig);
            $scriptPubKey = ScriptFactory::fromHex($test->scriptPubKey);
            $vectors[] = [
                $flags, $scriptSig, $scriptPubKey, $test->result, $test->desc, new Transaction
            ];
        }

        $flags = new ScriptInterpreterFlags(0);
        $vectors[] = [
            $flags,
            new Script(),
            ScriptFactory::create()->push(Buffer::hex(file_get_contents(__DIR__ . "/../Data/10010bytes.hex"))),
            false,
            'fails with >10000 bytes',
            new Transaction
        ];

        return $vectors;
    }


    /**
     * @dataProvider getScripts
     * @param ScriptInterpreterFlags $flags
     * @param ScriptInterface $scriptSig
     * @param ScriptInterface $scriptPubKey
     */
    public function testScript(ScriptInterpreterFlags $flags, ScriptInterface $scriptSig, ScriptInterface $scriptPubKey, $result, $description, $tx)
    {
        $i = new ScriptInterpreter(Bitcoin::getEcAdapter(), $tx, $flags);

        $i->setScript($scriptSig)->run();
        $testResult = $i->setScript($scriptPubKey)->run();

        $this->assertEquals($result, $testResult, $description);
    }/**/
}
