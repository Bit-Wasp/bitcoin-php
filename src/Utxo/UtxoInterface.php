<?php

namespace BitWasp\Bitcoin\Utxo;

use BitWasp\Bitcoin\Transaction\OutPoint;
use BitWasp\Bitcoin\Transaction\TransactionOutputInterface;

interface UtxoInterface
{
    /**
     * @return OutPoint
     */
    public function getOutPoint();

    /**
     * @return TransactionOutputInterface
     */
    public function getOutput();
}
