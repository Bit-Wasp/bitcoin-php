<?php

namespace BitWasp\Bitcoin\Transaction\Factory\Checker;

use BitWasp\Bitcoin\Script\Interpreter\CheckerBase;
use BitWasp\Bitcoin\Script\Interpreter\Checker;
use BitWasp\Bitcoin\Transaction\TransactionInterface;
use BitWasp\Bitcoin\Transaction\TransactionOutputInterface;

class CheckerCreator extends CheckerCreatorBase
{
    /**
     * @param TransactionInterface $tx
     * @param int $nInput
     * @param TransactionOutputInterface $txOut
     * @return CheckerBase
     */
    public function create(TransactionInterface $tx, $nInput, TransactionOutputInterface $txOut)
    {
        return new Checker($this->ecAdapter, $tx, $nInput, $txOut->getValue(), $this->txSigSerializer, $this->pubKeySerializer);
    }
}
