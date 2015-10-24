<?php

namespace BitWasp\Bitcoin\Tests\Transaction;


use BitWasp\Bitcoin\Address\AddressFactory;
use BitWasp\Bitcoin\Script\Script;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Bitcoin\Transaction\Locktime;
use BitWasp\Bitcoin\Transaction\Transaction;
use BitWasp\Bitcoin\Transaction\TransactionInput;
use BitWasp\Bitcoin\Transaction\TransactionInputCollection;
use BitWasp\Bitcoin\Transaction\TransactionOutput;
use BitWasp\Bitcoin\Transaction\TransactionOutputCollection;
use BitWasp\Bitcoin\Transaction\TxBuilder;
use BitWasp\Buffertools\Buffer;

class TxBuilderTest extends AbstractTestCase
{
    public function testDefault()
    {
        $builder = new TxBuilder();
        $tx = $builder->get();
        $this->assertEmpty($tx->getInputs());
        $this->assertEmpty($tx->getOutputs());
        $this->assertEquals(1, $tx->getVersion());
        $this->assertEquals(0, $tx->getLockTime());
    }

    public function testBuildsAndCanReset()
    {
        // Input
        $hashPrevOut = '0000000000000000000000000000000000000000000000000000000000000000';
        $nPrevOut = '0';
        $inputScript = new Script(new Buffer('abc'));
        $sequence = 10101;
        // Output
        $script = new Script(new Buffer('123'));
        $value = 50;

        $builder = new TxBuilder();
        $tx = $builder
            ->input($hashPrevOut, $nPrevOut, $inputScript, $sequence)
            ->output($value, $script)
            ->get();

        $input = $tx->getInput(0);
        $this->assertEquals($hashPrevOut, $input->getTransactionId());
        $this->assertEquals($nPrevOut, $input->getVout());
        $this->assertEquals($inputScript, $input->getScript());
        $this->assertEquals($sequence, $input->getSequence());

        $output = $tx->getOutput(0);
        $this->assertEquals($script, $output->getScript());
        $this->assertEquals($value, $output->getValue());

        $again = $builder->getAndReset();
        $this->assertEquals($tx, $again);

        $reset = $builder->get();
        $this->assertNotEquals($tx, $reset);
    }

    public function testSpendsOutputFrom()
    {
        $parent = new Transaction(
            1,
            new TransactionInputCollection([]),
            new TransactionOutputCollection([
                new TransactionOutput(50, new Script())
            ])
        );

        $parentHash = $parent->getTransactionId();

        $builder = new TxBuilder();
        $builder->spendOutputFrom($parent, 0);
        $tx = $builder->get();

        $input = $tx->getInput(0);
        $this->assertEquals($parentHash, $input->getTransactionId());
        $this->assertEquals(0, $input->getVout());
    }

    public function testPayToAddress()
    {
        $addressStr = '1KnHL81THzfp7tfFqHYWwo4GnY1L2rt4pk';
        $address = AddressFactory::fromString($addressStr);
        $value = 50;

        $builder = new TxBuilder();
        $builder->payToAddress($address, $value);
        $tx = $builder->get();

        $output = $tx->getOutput(0);
        $this->assertEquals(ScriptFactory::scriptPubKey()->payToAddress($address)->getBinary(), $output->getScript()->getBinary());
        $this->assertEquals($value, $output->getValue());
    }

    public function testSetMethods()
    {
        $version = 10;
        $locktime = 100;

        $builder = new TxBuilder();
        $tx = $builder
            ->version($version)
            ->input('a', 1)
            ->input('b', 10)
            ->output(50, new Script(new Buffer('script')))
            ->locktime($locktime)
            ->get();

        $this->assertEquals($version, $tx->getVersion());
        $this->assertEquals($locktime, $tx->getLockTime());
        $this->assertEquals(2, count($tx->getInputs()));
        $this->assertEquals(1, count($tx->getOutputs()));
    }

    public function testLocktime()
    {
        $locktime = new Locktime($this->safeMath());
        $blockHeight = 389356;
        $blockHeightLocktime = $locktime->fromBlockHeight($blockHeight);

        $timestamp = 123123123;
        $timestampLocktime = $locktime->fromTimestamp($timestamp);
        $builder = new TxBuilder();

        $this->assertEquals($blockHeightLocktime, $builder->lockToBlockHeight($locktime, $blockHeight)->getAndReset()->getLockTime());
        $this->assertEquals($timestampLocktime, $builder->lockToTimestamp($locktime, $timestamp)->getAndReset()->getLockTime());
    }
}