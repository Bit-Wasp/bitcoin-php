<?php

namespace BitWasp\Bitcoin\Tests\Script\ScriptInfo;

use BitWasp\Bitcoin\Key\PrivateKeyFactory;
use BitWasp\Bitcoin\Script\Classifier\OutputClassifier;
use BitWasp\Bitcoin\Script\Opcodes;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Script\ScriptInfo\PayToPubkey;
use BitWasp\Bitcoin\Tests\AbstractTestCase;

class PaytoPubkeyTest extends AbstractTestCase
{
    public function testMethods()
    {
        $priv = PrivateKeyFactory::create();
        $pub = $priv->getPublicKey();

        $script = ScriptFactory::sequence([$pub->getBuffer(), Opcodes::OP_CHECKSIG]);
        $classifier = new OutputClassifier();
        $this->assertEquals(OutputClassifier::PAYTOPUBKEY, $classifier->classify($script));

        $info = new PayToPubkey($script);
        $this->assertEquals(1, $info->getRequiredSigCount());
        $this->assertEquals(1, $info->getKeyCount());
        $this->assertEquals($pub->getBuffer(), $info->getKeys()[0]->getBuffer());
        $this->assertTrue($info->checkInvolvesKey($pub));

        $otherPriv = PrivateKeyFactory::create();
        $otherPub = $otherPriv->getPublicKey();

        $this->assertFalse($info->checkInvolvesKey($otherPub));
    }
}
