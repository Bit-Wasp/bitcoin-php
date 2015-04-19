<?php


namespace BitWasp\Bitcoin\Tests\Transaction;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Crypto\EcAdapter\EcAdapterFactory;
use BitWasp\Bitcoin\Key\PrivateKeyFactory;
use BitWasp\Bitcoin\Script\RedeemScript;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Script\Classifier\OutputClassifier;
use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Bitcoin\Transaction\TransactionBuilderInputState;

class TransactionBuilderInputStateTest extends AbstractTestCase
{
    public function getScripts()
    {
        $privateKey = PrivateKeyFactory::create();
        $pkh = ScriptFactory::scriptPubKey()->payToPubKeyHash($privateKey->getPublicKey());

        $rs = $this->getRedeemScript();

        return [
            [$pkh, OutputClassifier::PAYTOPUBKEYHASH, 1, null],
            [$rs->getOutputScript(), OutputClassifier::PAYTOSCRIPTHASH, 2, $rs],
            [ScriptFactory::scriptPubKey()->payToPubKey($privateKey->getPublicKey()), OutputClassifier::PAYTOPUBKEY, 1, null]
        ];
    }

    private function getRedeemScript()
    {
        $script = ScriptFactory::multisig(2, [
            PrivateKeyFactory::create()->getPublicKey(),
            PrivateKeyFactory::create()->getPublicKey(),
            PrivateKeyFactory::create()->getPublicKey()
        ]);

        return $script;
    }

    private function getOutputScript()
    {
        $script = ScriptFactory::scriptPubKey()
            ->payToAddress(PrivateKeyFactory::create()->getAddress());

        return $script;
    }

    private function createState(ScriptInterface $script, RedeemScript $rs = null)
    {
        $math = Bitcoin::getMath();
        $G = Bitcoin::getGenerator();
        $ecAdapter = EcAdapterFactory::getAdapter($math, $G);
        return new TransactionBuilderInputState($ecAdapter, $script, $rs);
    }

    /**
     * @dataProvider getScripts
     * @param ScriptInterface $script
     * @param RedeemScript $rs
     * @param string $outputType
     */
    public function testCreateFromScripts(ScriptInterface $script, $outputType, $nReqSig, RedeemScript $rs = null)
    {
        $state = $this->createState($script, $rs);
        $this->assertEquals($outputType, $state->getPrevOutType());
        $this->assertEquals($nReqSig, $state->getRequiredSigCount());
        $this->assertEquals(0, $state->getSigCount());
    }

    public function testCreateState()
    {
        $rs = $this->getRedeemScript();
        $outputScript = $rs->getOutputScript();

        $state = $this->createState($outputScript, $rs);
        $this->assertSame($outputScript, $state->getPrevOutScript());
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
        $outputScript = $this->getOutputScript();
        $state = $this->createState($outputScript);
        $state->getRedeemScript();
    }

    public function testGetEmptyValues()
    {
        $outputScript = $this->getOutputScript();
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
        $rs = $this->getRedeemScript();
        $script = $rs->getOutputScript();

        $this->createState($script);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage
     */
    public function testNonStandardScriptFails()
    {
        $script = ScriptFactory::create()->push('abab');
        $this->createState($script);
    }
}
