<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Tests\Transaction\Factory;

use BitWasp\Bitcoin\Address\AddressCreator;
use BitWasp\Bitcoin\Address\AddressInterface;
use BitWasp\Bitcoin\Address\PayToPubKeyHashAddress;
use BitWasp\Bitcoin\Address\ScriptHashAddress;
use BitWasp\Bitcoin\Crypto\Random\Random;
use BitWasp\Bitcoin\Key\Factory\PrivateKeyFactory;
use BitWasp\Bitcoin\Locktime;
use BitWasp\Bitcoin\Script\Script;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Bitcoin\Transaction\Factory\TxBuilder;
use BitWasp\Bitcoin\Transaction\Transaction;
use BitWasp\Bitcoin\Transaction\TransactionOutput;
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
        $hashPrevOut = Buffer::hex('0000000000000000000000000000000000000000000000000000000000000000', 32);
        $nPrevOut = 0;
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
        $outpoint = $input->getOutPoint();
        $this->assertEquals($hashPrevOut, $outpoint->getTxId());
        $this->assertEquals($nPrevOut, $outpoint->getVout());
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
        $parent = new Transaction(1, [], [
            new TransactionOutput(50, new Script())
        ]);

        $parentHash = $parent->getTxId();

        $builder = new TxBuilder();
        $builder->spendOutputFrom($parent, 0);
        $tx = $builder->get();

        $input = $tx->getInput(0);
        $this->assertEquals($parentHash, $input->getOutPoint()->getTxId());
        $this->assertEquals(0, $input->getOutPoint()->getVout());
    }

    public function testPayToAddress()
    {
        $addressStr = '1KnHL81THzfp7tfFqHYWwo4GnY1L2rt4pk';
        $addrCreator = new AddressCreator();
        $address = $addrCreator->fromString($addressStr);
        $value = 50;

        $builder = new TxBuilder();
        $builder->payToAddress($value, $address);
        $tx = $builder->get();

        $output = $tx->getOutput(0);
        $script = $address->getScriptPubKey();
        $this->assertEquals($script->getBinary(), $output->getScript()->getBinary());
        $this->assertEquals($value, $output->getValue());
    }

    public function testSetMethods()
    {
        $version = 10;
        $locktime = 100;

        $builder = new TxBuilder();
        $tx = $builder
            ->version($version)
            ->input('aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa', 1)
            ->input('baaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa', 10)
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
        $locktime = new Locktime();
        $blockHeight = 389356;
        $blockHeightLocktime = $locktime->fromBlockHeight($blockHeight);

        $timestamp = 123123123;
        $timestampLocktime = $locktime->fromTimestamp($timestamp);
        $builder = new TxBuilder();

        $this->assertEquals($blockHeightLocktime, $builder->lockToBlockHeight($locktime, $blockHeight)->getAndReset()->getLockTime());
        $this->assertEquals($timestampLocktime, $builder->lockToTimestamp($locktime, $timestamp)->getAndReset()->getLockTime());
    }

    public function getAddresses()
    {
        $factory = new PrivateKeyFactory(false);
        $key = $factory->generate(new Random());
        $script = ScriptFactory::scriptPubKey()->multisig(1, [$key->getPublicKey()]);
        $scriptAddress = new ScriptHashAddress($script->getScriptHash());
        return [
            [new PayToPubKeyHashAddress($key->getPubKeyHash())],
            [$scriptAddress],
        ];
    }

    /**
     * @dataProvider getAddresses
     * @param AddressInterface $address
     */
    public function testPayToAddress2(AddressInterface $address)
    {
        $expectedScript = $address->getScriptPubKey();

        $builder = new TxBuilder();
        $builder->payToAddress(50, $address);

        $this->assertEquals($expectedScript, $builder->get()->getOutput(0)->getScript());
    }
}
