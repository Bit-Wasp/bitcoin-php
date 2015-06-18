<?php

namespace BitWasp\Bitcoin\Tests\Script\Interpreter;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Script\ConsensusFactory;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Bitcoin\Transaction\Transaction;

class InterpreterFactoryTest extends AbstractTestCase
{

    /**
     * @var string
     */
    public $nativeType = '\BitWasp\Bitcoin\Script\Interpreter\Interpreter';

    public function getInterpreterFactory()
    {
        $consensus = new ConsensusFactory(Bitcoin::getEcAdapter());
        $flags = $consensus->defaultFlags();
        return $consensus->interpreterFactory($flags);
    }

    public function testGetNativeInterpreter()
    {
        $factory = $this->getInterpreterFactory();
        $interpreter = $factory->create(new Transaction());
        $this->assertInstanceOf($this->nativeType, $interpreter);
    }
}
