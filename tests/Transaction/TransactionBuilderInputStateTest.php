<?php


namespace BitWasp\Bitcoin\Tests\Transaction;

use BitWasp\Bitcoin\Crypto\EcAdapter\EcAdapterFactory;
use BitWasp\Bitcoin\Key\PrivateKeyFactory;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Script\Classifier\OutputClassifier;
use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Bitcoin\Transaction\Factory\TxSignerContext;
use BitWasp\Buffertools\Buffer;

class TransactionBuilderInputStateTest extends AbstractTestCase
{

    private function getRandomOutputScript()
    {
        return ScriptFactory::scriptPubKey()->payToAddress(PrivateKeyFactory::create()->getAddress());
    }

    private function getRandomRedeemScript()
    {
        $script = ScriptFactory::multisigNew(2, [
            PrivateKeyFactory::create()->getPublicKey(),
            PrivateKeyFactory::create()->getPublicKey(),
            PrivateKeyFactory::create()->getPublicKey()
        ]);

        return $script;
    }

    public function getScripts()
    {
        $privateKey = PrivateKeyFactory::create();
        $pkh = ScriptFactory::scriptPubKey()->payToPubKeyHash($privateKey->getPublicKey());

        $rs = $this->getRandomRedeemScript();
        $os = ScriptFactory::scriptPubKey()->payToScriptHash($rs);

        return [
            [$pkh, OutputClassifier::PAYTOPUBKEYHASH, 1, null],
            [$os, OutputClassifier::PAYTOSCRIPTHASH, 2, $rs],
            [ScriptFactory::scriptPubKey()->payToPubKey($privateKey->getPublicKey()), OutputClassifier::PAYTOPUBKEY, 1, null]
        ];
    }

    /**
     * @param ScriptInterface $script
     * @param ScriptInterface|null $rs
     * @return TxSignerContext
     */
    private function createState(ScriptInterface $script, ScriptInterface $rs = null)
    {
        $math = $this->safeMath();
        $G = $this->safeGenerator();
        $ecAdapter = EcAdapterFactory::getAdapter($math, $G);
        return new TxSignerContext($ecAdapter, $script, $rs);
    }

    /**
     * @param ScriptInterface $script
     * @param $outputType
     * @param $nReqSig
     * @param ScriptInterface|null $rs
     * @dataProvider getScripts
     */
    public function testCreateFromScripts(ScriptInterface $script, $outputType, $nReqSig, ScriptInterface $rs = null)
    {
        $state = $this->createState($script, $rs);
        $this->assertEquals($outputType, $state->getPrevOutType());
        $this->assertEquals($nReqSig, $state->getRequiredSigCount());
        $this->assertEquals(0, $state->getSigCount());
    }

    public function testCreateState()
    {
        $rs = $this->getRandomRedeemScript();
        $outputScript = ScriptFactory::scriptPubKey()->payToScriptHash($rs);

        $state = $this->createState($outputScript, $rs);
        $this->assertSame($rs, $state->getRedeemScript());
        $this->assertEquals(OutputClassifier::PAYTOSCRIPTHASH, $state->getPrevOutType());
        $this->assertEquals(OutputClassifier::MULTISIG, $state->getScriptType());
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage No redeem script was set
     */
    public function testFailsWithoutRedeemScript()
    {
        $outputScript = $this->getRandomOutputScript();
        $state = $this->createState($outputScript);
        $state->getRedeemScript();
    }

    public function testGetEmptyValues()
    {
        $outputScript = $this->getRandomOutputScript();
        $state = $this->createState($outputScript);
        $this->assertEquals(0, $state->getSigCount());

        $this->assertInternalType('array', $state->getPublicKeys());
        $this->assertEmpty($state->getPublicKeys());


        $this->assertInternalType('array', $state->getSignatures());
        $this->assertEmpty($state->getSignatures());
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Redeem script is required when output is P2SH
     */
    public function testRedeemScriptPassedWhenRequired()
    {
        $rs = $this->getRandomRedeemScript();
        $script = ScriptFactory::scriptPubKey()->payToScriptHash($rs);

        $this->createState($script);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage
     */
    public function testNonStandardScriptFails()
    {
        $script = ScriptFactory::create()->push(new Buffer('abab'))->getScript();
        $this->createState($script);
    }
}
