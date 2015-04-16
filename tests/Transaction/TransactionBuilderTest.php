<?php

namespace BitWasp\Bitcoin\Tests\Transaction;


use BitWasp\Bitcoin\Address\AddressInterface;
use BitWasp\Bitcoin\Key\PrivateKeyFactory;
use BitWasp\Bitcoin\Script\Script;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Bitcoin\Transaction\Transaction;
use BitWasp\Bitcoin\Transaction\TransactionFactory;
use BitWasp\Bitcoin\Transaction\TransactionInput;
use BitWasp\Bitcoin\Transaction\TransactionOutput;

class TransactionBuilderTest extends AbstractTestCase
{
    public function testDefaultTransaction()
    {
        $tx = new Transaction();
        $builder = TransactionFactory::builder();

        $this->assertEquals($tx, $builder->getTransaction());
    }

    public function testCanAddOutput()
    {
        $output = new TransactionOutput(50, new Script());
        $builder = TransactionFactory::builder();
        $builder->addOutput($output);

        $this->assertEquals($output, $builder->getTransaction()->getOutputs()->getOutput(0));
    }

    public function testCanAddInput()
    {
        $input = new TransactionInput('5a4ebf66822b0b2d56bd9dc64ece0bc38ee7844a23ff1d7320a88c5fdb2ad3e2', 0);
        $builder = TransactionFactory::builder();
        $builder->addInput($input);

        $this->assertEquals($input, $builder->getTransaction()->getInputs()->getInput(0));
    }

    public function testTakesTransactionAsArgument()
    {
        $input = new TransactionInput('5a4ebf66822b0b2d56bd9dc64ece0bc38ee7844a23ff1d7320a88c5fdb2ad3e2', 0);
        $output = new TransactionOutput(50, new Script());

        $tx = new Transaction();
        $tx->getInputs()->addInput($input);
        $tx->getOutputs()->addOutput($output);

        $builder = TransactionFactory::builder($tx);
        $this->assertEquals($tx, $builder->getTransaction());
        $this->assertEquals($input, $builder->getTransaction()->getInputs()->getInput(0));
        $this->assertEquals($output, $builder->getTransaction()->getOutputs()->getOutput(0));
    }

    public function testSpendsTxOut()
    {
        $input = new TransactionInput('5a4ebf66822b0b2d56bd9dc64ece0bc38ee7844a23ff1d7320a88c5fdb2ad3e2', 0);
        $output = new TransactionOutput(50, new Script());

        $tx = new Transaction();
        $tx->getInputs()->addInput($input);
        $tx->getOutputs()->addOutput($output);

        $txid = $tx->getTransactionId();
        $nOut = 0;
        $builder = TransactionFactory::builder();
        $builder->spendOutput($tx, $nOut);
        $this->assertEquals($txid, $builder->getTransaction()->getInputs()->getInput(0)->getTransactionId());
        $this->assertEquals($nOut, $builder->getTransaction()->getInputs()->getInput(0)->getVout());
    }

    public function getAddresses()
    {
        $key = PrivateKeyFactory::create();
        $script = ScriptFactory::multisig(1, [$key->getPublicKey()]);

        return [
            [$key->getAddress()],
            [$script->getAddress()],
        ];
    }

    /**
     * @dataProvider getAddresses
     * @param AddressInterface $address
     */
    public function testPayToAddress(AddressInterface $address)
    {
        $expectedScript = ScriptFactory::scriptPubKey()->payToAddress($address);

        $builder = TransactionFactory::builder();
        $builder->payToAddress($address, 50);

        $this->assertEquals($expectedScript, $builder->getTransaction()->getOutputs()->getOutput(0)->getScript());
    }
}