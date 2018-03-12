<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Tests\Script\ScriptInfo;

use BitWasp\Bitcoin\Crypto\Random\Random;
use BitWasp\Bitcoin\Key\Factory\PrivateKeyFactory;
use BitWasp\Bitcoin\Script\Classifier\OutputClassifier;
use BitWasp\Bitcoin\Script\Opcodes;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Script\ScriptInfo\PayToPubkey;
use BitWasp\Bitcoin\Script\ScriptType;
use BitWasp\Bitcoin\Tests\AbstractTestCase;

class PaytoPubkeyTest extends AbstractTestCase
{
    public function testMethods()
    {
        $factory = new PrivateKeyFactory(false);
        $priv = $factory->generate(new Random());
        $pub = $priv->getPublicKey();

        $script = ScriptFactory::sequence([$pub->getBuffer(), Opcodes::OP_CHECKSIG]);
        $classifier = new OutputClassifier();
        $this->assertEquals(ScriptType::P2PK, $classifier->classify($script));

        $info = PayToPubkey::fromScript($script);
        $this->assertEquals(1, $info->getRequiredSigCount());
        $this->assertEquals(1, $info->getKeyCount());
        $this->assertTrue($pub->getBuffer()->equals($info->getKeyBuffer()));
        $this->assertTrue($info->checkInvolvesKey($pub));

        $otherPriv = $factory->generate(new Random());
        $otherPub = $otherPriv->getPublicKey();

        $this->assertFalse($info->checkInvolvesKey($otherPub));
    }
}
