<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Utxo;

use BitWasp\Bitcoin\Transaction\OutPointInterface;
use BitWasp\Bitcoin\Transaction\TransactionOutputInterface;

interface UtxoInterface
{
    /**
     * @return OutPointInterface
     */
    public function getOutPoint(): OutPointInterface;

    /**
     * @return TransactionOutputInterface
     */
    public function getOutput(): TransactionOutputInterface;
}
