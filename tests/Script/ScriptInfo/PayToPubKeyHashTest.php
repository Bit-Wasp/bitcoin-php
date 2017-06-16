<?php

namespace BitWasp\Bitcoin\Tests\Script\ScriptInfo;

use BitWasp\Bitcoin\Key\PrivateKeyFactory;
use BitWasp\Bitcoin\Script\Classifier\OutputClassifier;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Script\ScriptInfo\PayToPubkeyHash;
use BitWasp\Bitcoin\Script\ScriptType;
use BitWasp\Bitcoin\Tests\AbstractTestCase;

class PayToPubkeyHashTest extends AbstractTestCase
{
    public function testMethods()
    {
        $priv = PrivateKeyFactory::create();
        $pub = $priv->getPublicKey();

        $script = ScriptFactory::scriptPubKey()->payToPubKeyHash($pub->getPubKeyHash());
        $classifier = new OutputClassifier();
        $this->assertEquals(ScriptType::P2PKH, $classifier->classify($script));

        $info = new PayToPubkeyHash($script);
        $this->assertEquals(1, $info->getRequiredSigCount());
        $this->assertEquals(1, $info->getKeyCount());
        $this->assertTrue($info->checkInvolvesKey($pub));

        $otherpriv = PrivateKeyFactory::create();
        $otherpub = $otherpriv->getPublicKey();
        $this->assertFalse($info->checkInvolvesKey($otherpub));
    }
}
