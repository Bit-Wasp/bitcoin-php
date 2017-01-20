<?php

namespace BitWasp\Bitcoin\Transaction;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Bitcoin\Serializable;
use BitWasp\Bitcoin\Serializer\Transaction\OutPointSerializer;
use BitWasp\Bitcoin\Serializer\Transaction\TransactionInputSerializer;
use BitWasp\Buffertools\BufferInterface;

class TransactionInput extends Serializable implements TransactionInputInterface
{

    /**
     * @var OutPointInterface
     */
    private $outPoint;

    /**
     * @var ScriptInterface
     */
    private $script;

    /**
     * @var string|int
     */
    private $sequence;

    /**
     * @param OutPointInterface $outPoint
     * @param ScriptInterface $script
     * @param int $sequence
     */
    public function __construct(OutPointInterface $outPoint, ScriptInterface $script, $sequence = self::SEQUENCE_FINAL)
    {
        $this->outPoint = $outPoint;
        $this->script = $script;
        $this->sequence = $sequence;
    }

    /**
     * @return OutPointInterface
     */
    public function getOutPoint()
    {
        return $this->outPoint;
    }

    /**
     * @return ScriptInterface
     */
    public function getScript()
    {
        return $this->script;
    }

    /**
     * @return int
     */
    public function getSequence()
    {
        return $this->sequence;
    }

    /**
     * @param TransactionInputInterface $other
     * @return bool
     */
    public function equals(TransactionInputInterface $other)
    {
        if (!$this->outPoint->equals($other->getOutPoint())) {
            return false;
        }

        if (!$this->script->equals($other->getScript())) {
            return false;
        }

        return gmp_cmp(gmp_init($this->sequence), gmp_init($other->getSequence())) === 0;
    }

    /**
     * Check whether this transaction is a Coinbase transaction
     *
     * @return boolean
     */
    public function isCoinbase()
    {
        $outpoint = $this->outPoint;
        return $outpoint->getTxId()->getBinary() === str_pad('', 32, "\x00")
            && $outpoint->getVout() == 0xffffffff;
    }

    /**
     * @return bool
     */
    public function isFinal()
    {
        $math = Bitcoin::getMath();
        return $math->cmp(gmp_init($this->getSequence()), gmp_init(self::SEQUENCE_FINAL)) === 0;
    }

    /**
     * @return BufferInterface
     */
    public function getBuffer()
    {
        return (new TransactionInputSerializer(new OutPointSerializer()))->serialize($this);
    }
}
