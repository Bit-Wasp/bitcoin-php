<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Tests\Transaction\PSBT;

use BitWasp\Bitcoin\Key\Factory\PublicKeyFactory;
use BitWasp\Bitcoin\Script\P2shScript;
use BitWasp\Bitcoin\Script\Script;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Script\WitnessScript;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Bitcoin\Transaction\PSBT\PSBTInput;
use BitWasp\Bitcoin\Transaction\Transaction;
use BitWasp\Bitcoin\Transaction\TransactionOutput;
use BitWasp\Buffertools\Buffer;

class PSBTInputTest extends AbstractTestCase
{
    public function testUnknown()
    {
        $in = new PSBTInput();
        $this->assertEmpty($in->getUnknownFields());

        $unknown = [
            "\x20" => new Buffer(str_repeat("\x41", 33)),
        ];
        $in = new PSBTInput(null, null, null, null, null, null, null, null, null, $unknown);
        $this->assertEquals($unknown, $in->getUnknownFields());
    }

    /**
     * @expectedException \BitWasp\Bitcoin\Exceptions\InvalidPSBTException
     * @expectedExceptionMessage Cannot set non-witness tx as well as witness utxo
     */
    public function testRejectsUsageOfBothTxForms()
    {
        new PSBTInput(new Transaction(), new TransactionOutput(1, new Script()));
    }

    /**
     * @expectedException  \BitWasp\Bitcoin\Exceptions\InvalidPSBTException
     * @expectedExceptionMessage Witness txout not known
     */
    public function testRequestingUnknownTxOutCausesError()
    {
        $in = new PSBTInput();
        $in->getWitnessTxOut();
    }
    /**
     * @expectedException  \BitWasp\Bitcoin\Exceptions\InvalidPSBTException
     * @expectedExceptionMessage Transaction not known
     */
    public function testRequestingUnknownTxCausesError()
    {
        $in = new PSBTInput();
        $in->getNonWitnessTx();
    }
    /**
     * @expectedException  \BitWasp\Bitcoin\Exceptions\InvalidPSBTException
     * @expectedExceptionMessage Witness script not known
     */
    public function testUnknownWitnessScript()
    {
        $in = new PSBTInput();
        $in->getWitnessScript();
    }

    /**
     * @expectedException  \BitWasp\Bitcoin\Exceptions\InvalidPSBTException
     * @expectedExceptionMessage Redeem script not known
     */
    public function testUnknownRedeemScript()
    {
        $in = new PSBTInput();
        $in->getRedeemScript();
    }

    public function testHasNonWitnessTx()
    {
        $tx = new Transaction();
        $in = new PSBTInput($tx);
        $this->assertTrue($in->hasNonWitnessTx());
        $this->assertFalse($in->hasWitnessTxOut());
        $this->assertSame($tx, $in->getNonWitnessTx());
    }

    public function testHasWitnessTxOut()
    {
        $txOut = new TransactionOutput(1, new Script());
        $in = new PSBTInput(null, $txOut);
        $this->assertFalse($in->hasNonWitnessTx());
        $this->assertTrue($in->hasWitnessTxOut());
        $this->assertSame($txOut, $in->getWitnessTxOut());
    }

    public function testGetWitnessScript()
    {
        $in = new PSBTInput();
        $this->assertFalse($in->hasWitnessScript());
        $pubKeyFactory = new PublicKeyFactory();
        $pk = $pubKeyFactory->fromHex("03d09c122356c892c926a0781233594ef6fa18e982089c2c875942dcd108d0818e");
        $p2pkh = ScriptFactory::scriptPubKey()->p2pkh($pk->getPubKeyHash());
        $p2pkh_wit = new WitnessScript($p2pkh);

        $in = new PSBTInput(null, null, [], null, null, $p2pkh_wit);
        $this->assertTrue($in->hasWitnessScript());
        $this->assertSame($p2pkh_wit, $in->getWitnessScript());
    }

    public function testGetRedeemScript()
    {
        $in = new PSBTInput();
        $this->assertFalse($in->hasRedeemScript());
        $pubKeyFactory = new PublicKeyFactory();
        $pk = $pubKeyFactory->fromHex("03d09c122356c892c926a0781233594ef6fa18e982089c2c875942dcd108d0818e");
        $p2pkh = ScriptFactory::scriptPubKey()->p2pkh($pk->getPubKeyHash());
        $redeemScript = new P2shScript($p2pkh);

        $in = new PSBTInput(null, null, [], null, $redeemScript);
        $this->assertTrue($in->hasRedeemScript());
        $this->assertSame($redeemScript, $in->getRedeemScript());
    }
}
