<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Tests\Key\KeyToScript;

use BitWasp\Bitcoin\Key\KeyToScript\ScriptAndSignData;
use BitWasp\Bitcoin\Script\P2shScript;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Bitcoin\Transaction\Factory\SignData;
use BitWasp\Buffertools\Buffer;

class ScriptAndSignDataTest extends AbstractTestCase
{
    public function testScriptAndSignDataSpk()
    {
        $script1 = ScriptFactory::scriptPubKey()->p2pkh(new Buffer("A", 20));
        $signData = new SignData();

        $scriptAndSignData = new ScriptAndSignData($script1, $signData);

        $this->assertEquals($script1, $scriptAndSignData->getScriptPubKey());
        $this->assertEquals($signData, $scriptAndSignData->getSignData());
    }

    public function testScriptAndSignDataRs()
    {
        $redeemScript = new P2shScript(ScriptFactory::scriptPubKey()->p2pkh(new Buffer("A", 20)));
        $signData = (new SignData())
            ->p2sh($redeemScript)
        ;

        $scriptAndSignData = new ScriptAndSignData($redeemScript->getOutputScript(), $signData);

        $this->assertEquals($redeemScript->getOutputScript(), $scriptAndSignData->getScriptPubKey());
        $this->assertEquals($signData, $scriptAndSignData->getSignData());
        $this->assertEquals($redeemScript, $scriptAndSignData->getSignData()->getRedeemScript());
    }
}
