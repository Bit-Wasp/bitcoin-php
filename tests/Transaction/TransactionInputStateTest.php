<?php


namespace BitWasp\Bitcoin\Tests\Transaction;


use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Crypto\EcAdapter\EcAdapterFactory;
use BitWasp\Bitcoin\Key\PrivateKeyFactory;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Bitcoin\Transaction\TransactionBuilderInputState;

class TransactionInputStateTest extends AbstractTestCase
{
    public function getRedeemScript()
    {
        $script = ScriptFactory::multisig(2, [
            PrivateKeyFactory::create()->getPublicKey(),
            PrivateKeyFactory::create()->getPublicKey(),
            PrivateKeyFactory::create()->getPublicKey()
        ]);

        return $script;
    }

    public function getOutputScript()
    {
        $script = ScriptFactory::scriptPubKey()
            ->payToAddress(PrivateKeyFactory::create()->getAddress());

        return $script;
    }

    public function testCreateState()
    {
        $math = Bitcoin::getMath();
        $G = Bitcoin::getGenerator();
        $ecAdapter = EcAdapterFactory::getAdapter($math, $G);
        $rs = $this->getRedeemScript();
        $outputScript = $rs->getOutputScript();

        $state = new TransactionBuilderInputState($ecAdapter, $outputScript, $rs);
        $this->assertSame($outputScript, $state->getPrevOutScript());
        $this->assertSame($rs, $state->getRedeemScript());
    }
}