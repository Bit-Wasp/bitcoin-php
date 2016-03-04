<?php

namespace BitWasp\Bitcoin\Tests\Transaction;

use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Bitcoin\Transaction\Transaction;
use BitWasp\Bitcoin\Transaction\TransactionFactory;

class TransactionFactoryTest extends AbstractTestCase
{
    public function testBuilder()
    {
        $builder = TransactionFactory::build();
        $this->assertInstanceOf($this->txBuilderType, $builder);
    }

    public function testMutateSigner()
    {
        $signer = TransactionFactory::mutate(new Transaction());
        $this->assertInstanceOf($this->txMutatorType, $signer);
    }
}
