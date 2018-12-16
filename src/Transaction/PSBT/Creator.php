<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Transaction\PSBT;

use BitWasp\Bitcoin\Transaction\TransactionInterface;

class Creator
{
    public function createPsbt(TransactionInterface $tx, array $unknowns = []): PSBT
    {
        $nIn = count($tx->getInputs());
        $inputs = [];
        for ($i = 0; $i < $nIn; $i++) {
            $inputs[] = new PSBTInput();
        }
        $nOut = count($tx->getOutputs());
        $outputs = [];
        for ($i = 0; $i < $nOut; $i++) {
            $outputs[] = new PSBTOutput();
        }
        return new PSBT($tx, $unknowns, $inputs, $outputs);
    }
}
