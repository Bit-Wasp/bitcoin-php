<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Tests\Script;

use BitWasp\Bitcoin\Script\Consensus\BitcoinConsensus;
use BitWasp\Bitcoin\Script\Consensus\ConsensusInterface;
use BitWasp\Bitcoin\Script\Consensus\NativeConsensus;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Bitcoin\Script\ScriptWitnessInterface;

class ConsensusFactoryTest extends ScriptCheckTestBase
{
    public function testGetNativeConsensus()
    {
        $this->assertInstanceOf(NativeConsensus::class, ScriptFactory::getNativeConsensus());
    }

    public function getExpectedAdapter()
    {
        return extension_loaded('bitcoinconsensus')
            ? BitcoinConsensus::class
            : NativeConsensus::class;
    }

    public function testGetBitcoinConsensus()
    {
        if ($this->getExpectedAdapter() === BitcoinConsensus::class) {
            $this->assertInstanceOf(BitcoinConsensus::class, ScriptFactory::getBitcoinConsensus());
        }
    }

    public function testDefaultAdapter()
    {
        $this->assertInstanceOf($this->getExpectedAdapter(), ScriptFactory::consensus());
    }

    /**
     * @return array
     */
    public function prepareConsensusTests()
    {
        $adapters = $this->getConsensusAdapters($this->getEcAdapters());
        $vectors = [];
        foreach ($this->prepareTestData() as $fixture) {
            list ($flags, $returns, $scriptWitness, $scriptSig, $scriptPubKey, $amount, $strTest) = $fixture;
            foreach ($adapters as $consensusFixture) {
                list ($consensus) = $consensusFixture;

                if ($consensus instanceof BitcoinConsensus) {
                    // Some conditions are untestable because recent libbitcoinconsensus
                    // versions reject usage of some flags. We skip verification of some
                    // of these, should be a @todo determine how many of these tests are
                    // skipped
                    if ($flags !== ($flags & BITCOINCONSENSUS_VERIFY_ALL)) {
                        continue;
                    }
                }

                $vectors[] = [$consensus, $flags, $returns, $scriptWitness, $scriptSig, $scriptPubKey, $amount, $strTest];
            }
        }

        return $vectors;
    }

    /**
     * @param ConsensusInterface $consensus
     * @param int $flags
     * @param bool $expectedResult
     * @param ScriptWitnessInterface $scriptWitness
     * @param ScriptInterface $scriptSig
     * @param ScriptInterface $scriptPubKey
     * @param int $amount
     * @param string $strTest
     * @dataProvider prepareConsensusTests
     */
    public function testScript(
        ConsensusInterface $consensus,
        int $flags,
        bool $expectedResult,
        ScriptWitnessInterface $scriptWitness,
        ScriptInterface $scriptSig,
        ScriptInterface $scriptPubKey,
        int $amount,
        string $strTest
    ) {
        $create = $this->buildCreditingTransaction($scriptPubKey, $amount);
        $tx = $this->buildSpendTransaction($create, $scriptSig, $scriptWitness);
        $check = $consensus->verify($tx, $scriptPubKey, $flags, 0, $amount);

        $this->assertEquals($expectedResult, $check, $strTest);
    }
}
