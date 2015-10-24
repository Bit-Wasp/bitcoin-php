<?php

namespace BitWasp\Bitcoin\Transaction\Mutator;


use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Bitcoin\Transaction\TransactionInput;
use BitWasp\Bitcoin\Transaction\TransactionInputInterface;

class InputMutator
{
    /**
     * @var TransactionInputInterface
     */
    private $input;

    /**
     * @param TransactionInputInterface $input
     */
    public function __construct(TransactionInputInterface $input)
    {
        $this->input = $input;
    }

    /**
     * @return TransactionInputInterface
     */
    public function get()
    {
        return $this->input;
    }

    /**
     * @param array $array
     * @return $this
     */
    private function replace(array $array = [])
    {
        $this->input = new TransactionInput(
            isset($array['txid']) ? $array['txid'] : $this->input->getTransactionId(),
            isset($array['vout']) ? $array['vout'] : $this->input->getVout(),
            isset($array['script']) ? $array['script'] : $this->input->getScript(),
            isset($array['nSequence']) ? $array['nSequence'] : $this->input->getSequence()
        );

        return $this;
    }

    /**
     * @return $this
     */
    public function null()
    {
        return $this->replace(array('txid' => '0000000000000000000000000000000000000000000000000000000000000000', 'vout' => 0xffffffff));
    }

    /**
     * @param string $txid
     * @return $this
     */
    public function txid($txid)
    {
        return $this->replace(array('txid' => $txid));
    }

    /**
     * @param int $vout
     * @return InputMutator
     */
    public function vout($vout)
    {
        return $this->replace(array('vout' => $vout));
    }

    /**
     * @param ScriptInterface $script
     * @return $this
     */
    public function script(ScriptInterface $script)
    {
        return $this->replace(array('script' => $script));
    }

    /**
     * @param int $nSequence
     * @return $this
     */
    public function sequence($nSequence)
    {
        return $this->replace(array('nSequence' => $nSequence));
    }
}