<?php

namespace BitWasp\Bitcoin\Tests\Script\Interpreter;


use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Script\Interpreter\InterpreterFactory;
use BitWasp\Bitcoin\Script\Interpreter\InterpreterInterface;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Bitcoin\Transaction\Transaction;

class InterpreterFactoryTest  extends AbstractTestCase
{

    /**
     * @var string
     */
    public $nativeType = '\BitWasp\Bitcoin\Script\Interpreter\Native\NativeInterpreter';

    /**
     * @var string
     */
    public $extensionType = '\BitWasp\Bitcoin\Script\Interpreter\BitcoinConsensus\BitcoinConsensus';

    public function getExpectedClass()
    {
        return extension_loaded('bitcoinconsensus')
            ? $this->extensionType
            : $this->nativeType;
    }

    public function getInterpreterFactory()
    {
        return new InterpreterFactory(Bitcoin::getEcAdapter());
    }

    public function testGetFlags()
    {
        $factory = $this->getInterpreterFactory();
        $i = 2 << 3;
        $flags = $factory->flags($i);
        $this->assertEquals($i, $flags->getFlags());
    }

    public function testGetDefaultFlags()
    {
        $factory = $this->getInterpreterFactory();
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

    public function testGetNativeInterpreter()
    {
        $factory = $this->getInterpreterFactory();
        $interpreter = $factory->getNativeInterpreter(new Transaction(), $factory->defaultFlags());
        $this->assertInstanceOf($this->nativeType, $interpreter);
    }

    public function testGetBitcoinConsensusInterpreter()
    {
        $factory = $this->getInterpreterFactory();
        $interpreter = $factory->getBitcoinConsensus(new Transaction(), $factory->defaultFlags());
        $this->assertInstanceOf($this->extensionType, $interpreter);
    }

    public function testCreate()
    {
        $factory = $this->getInterpreterFactory();
        $this->assertInstanceOf($this->getExpectedClass(), $factory->create(new Transaction(), $factory->defaultFlags()));
    }
}