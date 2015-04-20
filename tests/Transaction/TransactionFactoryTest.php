<?php

namespace BitWasp\Bitcoin\Tests\Transaction;

use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Bitcoin\Transaction\TransactionFactory;
use BitWasp\Bitcoin\Transaction\TransactionInterface;

class TransactionFactoryTest extends AbstractTestCase
{
    public function testCreate()
    {
        $tx = TransactionFactory::create();
        $this->assertSame(TransactionInterface::DEFAULT_VERSION, $tx->getVersion());
        $this->assertEmpty($tx->getInputs()->getInputs());
        $this->assertEmpty($tx->getOutputs()->getOutputs());
    }
}
