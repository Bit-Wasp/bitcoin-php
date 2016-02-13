<?php

namespace BitWasp\Bitcoin\Tests\Script;

use BitWasp\Bitcoin\Script\Consensus\ConsensusInterface;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Bitcoin\Transaction\TransactionFactory;

class ConsensusFactoryTest extends AbstractTestCase
{
    public function testGetNativeConsensus()
    {
        $this->assertInstanceOf($this->nativeConsensusInstance, ScriptFactory::getNativeConsensus(ScriptFactory::defaultFlags()));
    }

    public function getExpectedAdapter()
    {
        return extension_loaded('bitcoinconsensus')
            ? $this->libBitcoinConsensusInstance
            : $this->nativeConsensusInstance;
    }

    public function testGetBitcoinConsensus()
    {
        if ($this->getExpectedAdapter() == $this->libBitcoinConsensusInstance) {
            $this->assertInstanceOf($this->libBitcoinConsensusInstance, ScriptFactory::getBitcoinConsensus());
        }
    }

    public function testDefaultAdapter()
    {
        $this->assertInstanceOf($this->getExpectedAdapter(), ScriptFactory::consensus(ScriptFactory::defaultFlags()));
    }

    public function getVerifyVectors()
    {
        $f = file_get_contents('tests/Data/scriptinterpreter.simple.json');
        $json = json_decode($f);

        $vectors = [];
        foreach ($json->test as $c => $test) {
            $flags = $this->getInterpreterFlags($test->flags);

            $interpreter = ScriptFactory::getNativeConsensus($flags);
            $vectors[] = [$interpreter, $test->scriptSig, $test->scriptPubKey, $test->result];
            if (extension_loaded('bitcoinconsensus')) {
                $interpreter = ScriptFactory::getBitcoinConsensus($flags);
                $vectors[] = [$interpreter, $test->scriptSig, $test->scriptPubKey, $test->result];
            }
        }

        return $vectors;
    }

    /**
     * @dataProvider getVerifyVectors
     * @param ConsensusInterface $consensus
     * @param string $scriptSig
     * @param string $scriptPubKey
     * @param bool $expected
     */
    public function testVerify(ConsensusInterface $consensus, $scriptSig, $scriptPubKey, $expected)
    {
        $scriptSig = ScriptFactory::fromHex($scriptSig);
        $scriptPubKey = ScriptFactory::fromHex($scriptPubKey);
        $tx = TransactionFactory::build()
            ->input('0000000000000000000000000000000000000000000000000000000000000002', 0, $scriptSig)
            ->get();

        $result = $consensus->verify($tx, $scriptPubKey, 0, 0);

        $this->assertEquals($expected, $result, ScriptFactory::fromHex($scriptSig->getHex().$scriptPubKey->getHex())->getScriptParser()->getHumanReadable());
    }
}
