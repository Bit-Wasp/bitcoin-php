<?php

namespace BitWasp\Bitcoin\Tests\Script;

use BitWasp\Bitcoin\Script\Consensus\BitcoinConsensus;
use BitWasp\Bitcoin\Script\Consensus\NativeConsensus;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Tests\AbstractTestCase;

class ConsensusFactoryTest extends AbstractTestCase
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
}
