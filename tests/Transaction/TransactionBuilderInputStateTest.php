<?php


namespace BitWasp\Bitcoin\Tests\Transaction;


use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Crypto\EcAdapter\EcAdapterFactory;
use BitWasp\Bitcoin\Crypto\EcAdapter\EcAdapterInterface;
use BitWasp\Bitcoin\Key\PrivateKeyFactory;
use BitWasp\Bitcoin\Script\RedeemScript;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Script\Classifier\OutputClassifier;
use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Bitcoin\Transaction\TransactionBuilderInputState;

class TransactionBuilderInputStateTest extends AbstractTestCase
{
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

    public function testGetEmptyPublicKeys(){

    }
}