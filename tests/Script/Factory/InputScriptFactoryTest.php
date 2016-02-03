<?php

namespace BitWasp\Bitcoin\Tests\Script\Factory;

use BitWasp\Bitcoin\Key\PrivateKeyFactory;
use BitWasp\Bitcoin\Key\PublicKeyFactory;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Signature\TransactionSignatureFactory;
use BitWasp\Bitcoin\Tests\AbstractTestCase;

class InputScriptFactoryTest extends AbstractTestCase
{


    public function testPayToPubKey()
    {
        $txSigHex = '3045022100dbc87baa1b3a0225566728a0e01b8aaeedfc5a94368708bfa221302302f5458a022032df06bc5b894f848e62197d3aab328e5c8ce97ac3119ba5caacac5221d4534901';
        $txSig = TransactionSignatureFactory::fromHex($txSigHex);

        $script = ScriptFactory::scriptSig()->payToPubKey($txSig);
        $parsed = $script->getScriptParser()->decode();
        $this->assertSame($txSigHex, $parsed[0]->getData()->getHex());
        $this->assertEquals(1, count($parsed));
    }

    public function testPayToPubKeyHash()
    {
        $txSigHex = '3045022100dbc87baa1b3a0225566728a0e01b8aaeedfc5a94368708bfa221302302f5458a022032df06bc5b894f848e62197d3aab328e5c8ce97ac3119ba5caacac5221d4534901';
        $publicKeyHex = '0479d1e13ab70eff395460436ad5877b353689915c8ccb813f08682a8573556babdb528ef4a8caeda3ce07c0474ce2a7dc4054ca5e75464bbb7deb73c95331de17';

        $txSig = TransactionSignatureFactory::fromHex($txSigHex);
        $publicKey = PublicKeyFactory::fromHex($publicKeyHex);

        $script = ScriptFactory::scriptSig()->payToPubKeyHash($txSig, $publicKey);
        $parsed = $script->getScriptParser()->decode();
        $this->assertSame($txSigHex, $parsed[0]->getData()->getHex());
        $this->assertSame($publicKeyHex, $parsed[1]->getData()->getHex());
        $this->assertEquals(2, count($parsed));
    }

    public function testPayToScriptHashMultisig()
    {
        // Script::payToScriptHash should produce a ScriptHash type script, from a different script
        $private = PrivateKeyFactory::create();
        $script = ScriptFactory::scriptPubKey()->multisig(1, [$private->getPublicKey()]);

        $sigHex = '3045022100dbc87baa1b3a0225566728a0e01b8aaeedfc5a94368708bfa221302302f5458a022032df06bc5b894f848e62197d3aab328e5c8ce97ac3119ba5caacac5221d4534901';
        $sig = TransactionSignatureFactory::fromHex($sigHex);
        $sigs = [$sig];

        $inputScript = ScriptFactory::scriptSig()->multisig($sigs);
        $scriptHashSig = ScriptFactory::scriptSig()->payToScriptHash($inputScript, $script);
        $parsed = $scriptHashSig->getScriptParser()->decode();

        $this->assertSame('', $parsed[0]->getData()->getHex());
        $this->assertSame($sigHex, $parsed[1]->getData()->getHex());
        $this->assertSame($script->getHex(), $parsed[2]->getData()->getHex());
    }
}
