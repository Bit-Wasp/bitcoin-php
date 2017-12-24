<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Tests\Transaction\Factory;

use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Bitcoin\Transaction\Factory\SignData;

class SignDataTest extends AbstractTestCase
{
    public function getVectors()
    {
        $fixtures = $this->jsonDataFile('signdata_fixtures.json');
        $vectors = [];
        foreach ($fixtures['fixtures'] as $fixture) {
            $vectors[] = [
                $fixture['redeemScript'] !== '' ? ScriptFactory::fromHex($fixture['redeemScript']) : null,
                $fixture['witnessScript'] !== '' ? ScriptFactory::fromHex($fixture['witnessScript']) : null,
                $fixture['signaturePolicy'] !== '' ? $this->getScriptFlagsFromString($fixture['signaturePolicy']) : null
            ];
        }
        return $vectors;
    }

    /**
     * @param ScriptInterface|null $rs
     * @param ScriptInterface|null $ws
     * @param int|null $flags
     * @dataProvider getVectors
     */
    public function testCase(ScriptInterface $rs = null, ScriptInterface $ws = null, int $flags = null)
    {
        $signData = new SignData();
        $this->assertFalse($signData->hasRedeemScript());
        $this->assertFalse($signData->hasWitnessScript());
        $this->assertFalse($signData->hasSignaturePolicy());

        if ($rs !== null) {
            $signData->p2sh($rs);
            $this->assertTrue($signData->hasRedeemScript());
            $this->assertEquals($rs, $signData->getRedeemScript());
        }

        if ($ws!== null) {
            $signData->p2wsh($ws);
            $this->assertTrue($signData->hasWitnessScript());
            $this->assertEquals($ws, $signData->getWitnessScript());
        }

        if ($flags !== null) {
            $signData->signaturePolicy($flags);
            $this->assertTrue($signData->hasSignaturePolicy());
            $this->assertEquals($flags, $signData->getSignaturePolicy());
        }
    }

    public function getThrowVectors()
    {
        return [
            ['getWitnessScript', '\\RuntimeException', 'Witness script requested but not set'],
            ['getRedeemScript', '\\RuntimeException', 'Redeem script requested but not set'],
            ['getSignaturePolicy', '\\RuntimeException', 'Signature policy requested but not set'],
        ];
    }

    /**
     * @param $function
     * @param $expectedException
     * @param $expectedExceptionMessage
     * @dataProvider getThrowVectors
     */
    public function testThrowsIfUnknownDataRequested($function, $expectedException, $expectedExceptionMessage)
    {
        $signData = new SignData();
        try {
            $signData->{$function}();
        } catch (\Exception $caught) {
            $this->assertInstanceOf($expectedException, $caught);
            $this->assertEquals($expectedExceptionMessage, $caught->getMessage());
            return;
        }

        throw new \RuntimeException('Test failed to complete');
    }
}
