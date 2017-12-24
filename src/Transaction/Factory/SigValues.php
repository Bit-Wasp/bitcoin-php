<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Transaction\Factory;

use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Bitcoin\Script\ScriptWitnessInterface;

class SigValues
{
    /**
     * @var ScriptInterface
     */
    private $scriptSig;

    /**
     * @var ScriptWitnessInterface
     */
    private $scriptWitness;

    /**
     * SigValues constructor.
     * @param ScriptInterface $scriptSig
     * @param ScriptWitnessInterface $scriptWitness
     */
    public function __construct(ScriptInterface $scriptSig, ScriptWitnessInterface $scriptWitness)
    {
        $this->scriptSig = $scriptSig;
        $this->scriptWitness = $scriptWitness;
    }

    /**
     * @return ScriptInterface
     */
    public function getScriptSig(): ScriptInterface
    {
        return $this->scriptSig;
    }

    /**
     * @return ScriptWitnessInterface
     */
    public function getScriptWitness(): ScriptWitnessInterface
    {
        return $this->scriptWitness;
    }
}
