<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Tests\Transaction;

use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Bitcoin\Transaction\Factory\TxBuilder;
use BitWasp\Bitcoin\Transaction\Mutator\TxMutator;
use BitWasp\Bitcoin\Transaction\Transaction;
use BitWasp\Bitcoin\Transaction\TransactionFactory;

class TransactionFactoryTest extends AbstractTestCase
{
    public function testBuilder()
    {
        $builder = TransactionFactory::build();
        $this->assertInstanceOf(TxBuilder::class, $builder);
    }

    public function testMutateSigner()
    {
        $signer = TransactionFactory::mutate(new Transaction());
        $this->assertInstanceOf(TxMutator::class, $signer);
    }
}
