<?php

namespace BitWasp\Bitcoin\Tests\Script;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Script\ConsensusFactory;
use BitWasp\Bitcoin\Script\Interpreter\InterpreterInterface;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Bitcoin\Transaction\Transaction;

class ConsensusFactoryTest extends AbstractTestCase
{
    private $interpreterInstance = 'BitWasp\Bitcoin\Script\Interpreter\Interpreter';
    private $nativeConsensusInstance = 'BitWasp\Bitcoin\Script\Consensus\NativeConsensus';
    private $libBitcoinConsensusInstance = 'BitWasp\Bitcoin\Script\Consensus\BitcoinConsensus';

    public function getConsensusFactory()
    {
        return new ConsensusFactory(Bitcoin::getEcAdapter());
    }


    public function testGetFlags()
    {
        $factory = $this->getConsensusFactory();
        $i = 2 << 3;
        $flags = $factory->flags($i);
        $this->assertEquals($i, $flags->getFlags());
    }

    public function testGetDefaultFlags()
    {
        $factory = $this->getConsensusFactory();
        $flags = $factory->defaultFlags();
        foreach ([
                     InterpreterInterface::VERIFY_P2SH, InterpreterInterface::VERIFY_STRICTENC,
                     InterpreterInterface::VERIFY_DERSIG,InterpreterInterface::VERIFY_LOW_S,
                     InterpreterInterface::VERIFY_NULL_DUMMY, InterpreterInterface::VERIFY_SIGPUSHONLY,
                     InterpreterInterface::VERIFY_DISCOURAGE_UPGRADABLE_NOPS, InterpreterInterface::VERIFY_CLEAN_STACK
                 ] as $flag) {
            $this->assertTrue($flags->checkFlags($flag));
        }
    }

    public function testGetInterpreterFactory()
    {
        $factory = $this->getConsensusFactory();
        $interpreterFactory = $factory->interpreterFactory($factory->defaultFlags());

        $interpreter = $interpreterFactory->create(new Transaction);
        $this->assertInstanceOf($this->interpreterInstance, $interpreter);
    }

    public function testGetNativeConsensus()
    {
        $factory = $this->getConsensusFactory();
        $this->assertInstanceOf($this->nativeConsensusInstance, $factory->getNativeConsensus($factory->defaultFlags()));
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
            $factory = $this->getConsensusFactory();
            $this->assertInstanceOf($this->libBitcoinConsensusInstance, $factory->getBitcoinConsensus($factory->defaultFlags()));
        }
    }

    public function testDefaultAdapter()
    {
        $factory = $this->getConsensusFactory();
        $this->assertInstanceOf($this->getExpectedAdapter(), $factory->getConsensus($factory->defaultFlags()));
    }
}
