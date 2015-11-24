<?php

namespace BitWasp\Bitcoin\Tests\Script;


use BitWasp\Bitcoin\Script\Consensus\ConsensusInterface;
use BitWasp\Bitcoin\Script\Interpreter\InterpreterInterface;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Bitcoin\Transaction\TransactionFactory;

class ConsensusFactoryTest extends AbstractTestCase
{
    private $interpreterInstance = 'BitWasp\Bitcoin\Script\Interpreter\Interpreter';
    private $nativeConsensusInstance = 'BitWasp\Bitcoin\Script\Consensus\NativeConsensus';
    private $libBitcoinConsensusInstance = 'BitWasp\Bitcoin\Script\Consensus\BitcoinConsensus';


    public function testGetDefaultFlags()
    {
        $flags = ScriptFactory::defaultFlags();
        foreach ([
                     InterpreterInterface::VERIFY_P2SH, InterpreterInterface::VERIFY_STRICTENC,
                     InterpreterInterface::VERIFY_DERSIG,InterpreterInterface::VERIFY_LOW_S,
                     InterpreterInterface::VERIFY_NULL_DUMMY, InterpreterInterface::VERIFY_SIGPUSHONLY,
                     InterpreterInterface::VERIFY_DISCOURAGE_UPGRADABLE_NOPS, InterpreterInterface::VERIFY_CLEAN_STACK
                 ] as $flag) {
            $this->assertTrue($flags->checkFlags($flag));
        }
    }

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
        $this->assertInstanceOf($this->getExpectedAdapter(), ScriptFactory::consensus());
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

}
