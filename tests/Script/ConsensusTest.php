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
            $this->assertInstanceOf($this->libBitcoinConsensusInstance, ScriptFactory::getBitcoinConsensus(0));
        }
    }

    public function testDefaultAdapter()
    {
        $this->assertInstanceOf($this->getExpectedAdapter(), ScriptFactory::consensus(ScriptFactory::defaultFlags()));
    }
}
