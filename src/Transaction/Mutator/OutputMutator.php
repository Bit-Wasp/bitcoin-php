<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Transaction\Mutator;

use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Bitcoin\Transaction\TransactionOutput;
use BitWasp\Bitcoin\Transaction\TransactionOutputInterface;

class OutputMutator
{
    /**
     * @var TransactionOutputInterface
     */
    private $output;

    /**
     * @param TransactionOutputInterface $output
     */
    public function __construct(TransactionOutputInterface $output)
    {
        $this->output = $output;
    }

    /**
     * @return TransactionOutputInterface
     */
    public function done(): TransactionOutputInterface
    {
        return $this->output;
    }

    /**
     * @param array $array
     * @return $this
     */
    private function replace(array $array)
    {
        $this->output = new TransactionOutput(
            array_key_exists('value', $array) ? $array['value'] : $this->output->getValue(),
            array_key_exists('script', $array) ? $array['script'] : $this->output->getScript()
        );

        return $this;
    }

    /**
     * @param int $value
     * @return $this
     */
    public function value(int $value)
    {
        return $this->replace(array('value' => $value));
    }

    /**
     * @param ScriptInterface $script
     * @return $this
     */
    public function script(ScriptInterface $script)
    {
        return $this->replace(array('script' => $script));
    }
}
