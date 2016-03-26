<?php

namespace BitWasp\Bitcoin\Tests\Script\ScriptInfo;

use BitWasp\Bitcoin\Key\PrivateKeyFactory;
use BitWasp\Bitcoin\Script\Classifier\OutputClassifier;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Script\ScriptInfo\Multisig;
use BitWasp\Bitcoin\Script\ScriptInfo\PayToPubkey;
use BitWasp\Bitcoin\Script\ScriptInfo\PayToPubkeyHash;
use BitWasp\Bitcoin\Tests\AbstractTestCase;

class MultisigTest extends AbstractTestCase
{
    public function testMethods()
    {
        $priv = PrivateKeyFactory::create();
        $pub = $priv->getPublicKey();

        $otherpriv = PrivateKeyFactory::create();
        $otherpub = $otherpriv->getPublicKey();

        $script = ScriptFactory::scriptPubKey()->multisig(2, [$pub, $otherpub]);
        $classifier = new OutputClassifier();
        $this->assertEquals(OutputClassifier::MULTISIG, $classifier->classify($script));

        $info = new Multisig($script);
        $this->assertEquals(2, $info->getRequiredSigCount());
        $this->assertEquals(2, $info->getKeyCount());
        $this->assertTrue($info->checkInvolvesKey($pub));
        $this->assertTrue($info->checkInvolvesKey($otherpub));

        $unrelatedPub = PrivateKeyFactory::create()->getPublicKey();
        $this->assertFalse($info->checkInvolvesKey($unrelatedPub));

    }
}
