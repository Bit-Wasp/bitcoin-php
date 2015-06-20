<?php

namespace BitWasp\Bitcoin\Utxo;

use BitWasp\Bitcoin\Transaction\TransactionInterface;
use Doctrine\Common\Cache\Cache;

class UtxoSet
{
    /**
     * @var Cache
     */
    private $contents;

    /**
     * @var int
     */
    private $size = 0;

    /**
     * @param Cache $cache
     */
    public function __construct(Cache $cache)
    {
        $this->contents = $cache;
    }

    /**
     * @param TransactionInterface $tx
     */
    public function add(TransactionInterface $tx)
    {
        $this->removeSpends($tx);
        $this->addOutputs($tx);
    }

    /**
     * @param $tx
     * @param $vout
     * @return bool
     */
    public function exists($tx, $vout)
    {
        return isset($this->contents[$tx][$vout]);
    }

    /**
     * @param $txid
     * @param $vout
     */
    public function remove($txid, $vout)
    {
        $this->contents->delete($this->cacheIndex($txid, $vout));
        $this->size--;
    }

    /**
     * @param TransactionInterface $tx
     */
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

    /**
     * @param $txid
     * @param $vout
     * @return string
     */
    private function cacheIndex($txid, $vout)
    {
        return "utxo_{$txid}_{$vout}";
    }

    /**
     * @param TransactionInterface $tx
     */
    public function addOutputs(TransactionInterface $tx)
    {
        $txid = $tx->getTransactionId();
        $vout = 0;

        foreach ($tx->getOutputs()->getOutputs() as $output) {
            $this->contents->save(
                $this->cacheIndex($txid, $vout),
                new Utxo(
                    $txid,
                    $vout++,
                    $output
                )
            );
        }

        $this->size += $vout;
    }

    /**
     * @return int
     */
    public function size()
    {
        return $this->size;
    }
}
