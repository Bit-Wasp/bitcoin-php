<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Transaction\PSBT;

use BitWasp\Bitcoin\Transaction\TransactionInterface;

class Creator
{
    public function createPsbt(TransactionInterface $tx, array $unknowns = []): PSBT
    {
        $inputs = [];
        for ($i = 0; $i < count($tx->getInputs()); $i++) {
            $inputs[] = new PSBTInput();
        }
        $outputs = [];
        for ($i = 0; $i < count($tx->getOutputs()); $i++) {
            $outputs[] = new PSBTOutput();
        }

        return new PSBT($tx, $unknowns, $inputs, $outputs);
    }
}
