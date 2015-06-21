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
     * @param $txid
     * @param $vout
     * @return string
     */
    private function cacheIndex($txid, $vout)
    {
        return "utxo_{$txid}_{$vout}";
    }

    /**
     * @param $txid
     * @param $vout
     */
    public function delete($txid, $vout)
    {
        $this->contents->delete($this->cacheIndex($txid, $vout));
        $this->size--;
    }

    /**
     * @param TransactionInterface $tx
     */
    private function deleteSpends(TransactionInterface $tx)
    {
        foreach ($tx->getInputs()->getInputs() as $v => $input) {
            if (!$input->isCoinBase()) {
                $this->delete($input->getTransactionId(), $input->getVout());
            }
        }
    }

    /**
     * @param TransactionInterface $tx
     */
    private function saveOutputs(TransactionInterface $tx)
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
     * @param TransactionInterface $tx
     */
    public function save(TransactionInterface $tx)
    {
        $this->deleteSpends($tx);
        $this->saveOutputs($tx);
    }

    /**
     * @param string $txid
     * @param int $vout
     * @return Utxo
     */
    public function fetch($txid, $vout)
    {
        return $this->contents->fetch($this->cacheIndex($txid, $vout));
    }

    /**
     * @param string $tx
     * @param int $vout
     * @return bool
     */
    public function contains($tx, $vout)
    {
        return $this->contents->contains($this->cacheIndex($tx, $vout));
    }

    /**
     * @return int
     */
    public function size()
    {
        return $this->size;
    }
}
