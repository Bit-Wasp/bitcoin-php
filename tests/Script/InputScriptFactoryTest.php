<?php

namespace BitWasp\Bitcoin\Tests\Script;

use BitWasp\Bitcoin\Key\PrivateKeyFactory;
use BitWasp\Bitcoin\Key\PublicKeyFactory;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Script\Classifier\InputClassifier;
use BitWasp\Bitcoin\Signature\TransactionSignatureFactory;
use BitWasp\Bitcoin\Tests\AbstractTestCase;

class InputScriptFactoryTest extends AbstractTestCase
{
    public function testClassify()
    {
        $script = ScriptFactory::create();
        $classifier = ScriptFactory::scriptSig()->classify($script);
        $this->assertInstanceOf('BitWasp\Bitcoin\Script\Classifier\InputClassifier', $classifier);
    }

    public function testPayToPubKey()
    {
        $txSigHex = '3045022100dbc87baa1b3a0225566728a0e01b8aaeedfc5a94368708bfa221302302f5458a022032df06bc5b894f848e62197d3aab328e5c8ce97ac3119ba5caacac5221d4534901';
        $txSig = TransactionSignatureFactory::fromHex($txSigHex);

        $script = ScriptFactory::scriptSig()->payToPubKey($txSig);
        $parsed = $script->getScriptParser()->parse();
        $this->assertSame($txSigHex, $parsed[0]->getHex());
        $this->assertEquals(1, count($parsed));
        $this->assertEquals(InputClassifier::PAYTOPUBKEY, ScriptFactory::scriptSig()->classify($script)->classify());
    }

    public function testPayToPubKeyHash()
    {
        $txSigHex = '3045022100dbc87baa1b3a0225566728a0e01b8aaeedfc5a94368708bfa221302302f5458a022032df06bc5b894f848e62197d3aab328e5c8ce97ac3119ba5caacac5221d4534901';
        $publicKeyHex = '0479d1e13ab70eff395460436ad5877b353689915c8ccb813f08682a8573556babdb528ef4a8caeda3ce07c0474ce2a7dc4054ca5e75464bbb7deb73c95331de17';

        $txSig = TransactionSignatureFactory::fromHex($txSigHex);
        $publicKey = PublicKeyFactory::fromHex($publicKeyHex);

        $script = ScriptFactory::scriptSig()->payToPubKeyHash($txSig, $publicKey);
        $parsed = $script->getScriptParser()->parse();
        $this->assertSame($txSigHex, $parsed[0]->getHex());
        $this->assertSame($publicKeyHex, $parsed[1]->getHex());
        $this->assertEquals(2, count($parsed));
        $this->assertEquals(InputClassifier::PAYTOPUBKEYHASH, ScriptFactory::scriptSig()->classify($script)->classify());
    }

    public function testPayToMultisig()
    {
        // Script::payToScriptHash should produce a ScriptHash type script, from a different script
        $private = PrivateKeyFactory::create();
        $script = ScriptFactory::multisig(1, [$private->getPublicKey()]);

        $sigHex = '3045022100dbc87baa1b3a0225566728a0e01b8aaeedfc5a94368708bfa221302302f5458a022032df06bc5b894f848e62197d3aab328e5c8ce97ac3119ba5caacac5221d4534901';
        $sig = TransactionSignatureFactory::fromHex($sigHex);
        $sigs = [$sig];
        $scriptHash = ScriptFactory::scriptSig()->multisigP2sh($script, $sigs);
        $parsed = $scriptHash->getScriptParser()->parse();

        $this->assertSame('00', $parsed[0]->getHex());
        $this->assertSame($sigHex, $parsed[1]->getHex());
        $this->assertSame($script->getHex(), $parsed[2]->getHex());
        $this->assertEquals(InputClassifier::MULTISIG, ScriptFactory::scriptSig()->classify($scriptHash)->classify());
    }
}
