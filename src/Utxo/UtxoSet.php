<?php
/**
 * Created by PhpStorm.
 * User: thomas
 * Date: 03/06/15
 * Time: 02:57
 */

namespace BitWasp\Bitcoin\Utxo;


use BitWasp\Bitcoin\Transaction\TransactionInterface;

class UtxoSet
{
    /**
     * @var array
     */
    private $contents = [];

    /**
     * @var int
     */
    private $size = 0;

    /**
     * @param TransactionInterface $tx
     */
    public function add(TransactionInterface $tx)
    {
        $this->removeSpends($tx);
        $this->addOutputs($tx);
    }

    public function exists($tx, $vout)
    {
        return isset($this->contents[$tx][$vout]);
    }

    public function remove($txid, $vout)
    {
        unset($this->contents[$vout]);
        $this->size--;
    }

    public function removeSpends(TransactionInterface $tx)
    {
        $inc = 0;
        foreach ($tx->getInputs()->getInputs() as $v => $input) {
            if (!$input->isCoinBase()) {
                $this->remove($input->getTransactionId(), $input->getVout());
                $inc++;
            }
        }

        $this->size -= $inc;
    }

    public function addOutputs(TransactionInterface $tx)
    {
        $txid = $tx->getTransactionId();
        $inc = 0;
        foreach ($tx->getOutputs()->getOutputs() as $v => $output) {
            $utxo = new Utxo(
                $txid,
                $v,
                $output
            );

            $this->contents[$txid][$v] = $utxo;
            $inc++;
        }
        
        $this->size += $inc;
    }

    /**
     * @return int
     */
    public function size()
    {
        return $this->size;
    }

}