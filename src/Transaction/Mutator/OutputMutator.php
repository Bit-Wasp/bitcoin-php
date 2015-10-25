<?php

namespace BitWasp\Bitcoin\Transaction\Mutator;

use BitWasp\Bitcoin\Script\Script;
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
    public function get()
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
            isset($array['value']) ? $array['value'] : $this->output->getValue(),
            isset($array['script']) ? $array['script'] : $this->output->getScript()
        );

        return $this;
    }

    /**
     * @return $this
     */
    public function null()
    {
        return $this->replace(array('value' => '18446744073709551615', 'script' => new Script()));
    }

    /**
     * @param int $value
     * @return $this
     */
    public function value($value)
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
