<?php

namespace BitWasp\Bitcoin\Tests\Script;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Script\Consensus\ConsensusInterface;
use BitWasp\Bitcoin\Script\ConsensusFactory;
use BitWasp\Bitcoin\Script\Interpreter\InterpreterInterface;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Bitcoin\Transaction\Transaction;
use BitWasp\Bitcoin\Transaction\TransactionFactory;

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

    public function getVerifyVectors()
    {
        $f = file_get_contents('tests/Data/scriptinterpreter.simple.json');
        $json = json_decode($f);

        $consensusFactory = new \BitWasp\Bitcoin\Script\ConsensusFactory(\BitWasp\Bitcoin\Bitcoin::getEcAdapter());
        $vectors = [];
        foreach ($json->test as $c => $test) {
            $flags = $this->getInterpreterFlags($test->flags);

            $interpreter = $consensusFactory->getNativeConsensus($flags);
            $vectors[] = [$interpreter, $test->scriptSig, $test->scriptPubKey, $test->result];
            if (extension_loaded('bitcoinconsensus')) {
                $interpreter = $consensusFactory->getBitcoinConsensus($flags);
                $vectors[] = [$interpreter, $test->scriptSig, $test->scriptPubKey, $test->result];
            }
        }

        return $vectors;
    }

    /**
     * @dataProvider getVerifyVectors
     */
    public function testVerify(ConsensusInterface $consensus, $scriptSig, $scriptPubKey, $expected)
    {
        $scriptSig = ScriptFactory::fromHex($scriptSig);
        $scriptPubKey = ScriptFactory::fromHex($scriptPubKey);
        $tx = TransactionFactory::build()
            ->input('0000000000000000000000000000000000000000000000000000000000000002', 0, $scriptSig)
            ->get();

        $this->assertEquals($expected, $consensus->verify($tx, $scriptPubKey, 0), ScriptFactory::fromHex($scriptSig->getHex().$scriptPubKey->getHex())->getScriptParser()->getHumanReadable());
    }
/*
    public function testVerifyVectors()
    {

        $f = file_get_contents('tests/Data/scriptinterpreter.simple.json');
        $json = json_decode($f);

        $consensusFactory = new \BitWasp\Bitcoin\Script\ConsensusFactory(\BitWasp\Bitcoin\Bitcoin::getEcAdapter());

        foreach ($json->test as $c => $test) {
            $flags = $this->getInterpreterFlags($test->flags);
            $scriptSig = ScriptFactory::fromHex($test->scriptSig);
            $scriptPubKey = ScriptFactory::fromHex($test->scriptPubKey);
            $tx = TransactionFactory::build()
                ->input('0000000000000000000000000000000000000000000000000000000000000002', 0, $scriptSig)
                ->get();

            $interpreter = $consensusFactory->getNativeConsensus($flags);
            $r = $interpreter->verify($tx, $scriptPubKey, 0);
            $this->assertEquals($test->result, $r);

            if (extension_loaded('bitcoinconsensus')) {
                $interpreter = $consensusFactory->getBitcoinConsensus($flags);
                $r = $interpreter->verify($tx, $scriptPubKey, 0);
                $this->assertEquals($test->result, $r);
            }
        }
    }
*/
}
