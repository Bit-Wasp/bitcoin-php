<?php

namespace BitWasp\Bitcoin\Transaction;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Serializer\Transaction\OutPointSerializer;
use BitWasp\Bitcoin\Script\Script;
use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Bitcoin\Serializable;
use BitWasp\Bitcoin\Serializer\Transaction\TransactionInputSerializer;
use BitWasp\Buffertools\BufferInterface;
use BitWasp\CommonTrait\FunctionAliasArrayAccess;

class TransactionInput extends Serializable implements TransactionInputInterface
{
    use FunctionAliasArrayAccess;

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

        $this
            ->initFunctionAlias('outpoint', 'getOutPoint')
            ->initFunctionAlias('script', 'getScript')
            ->initFunctionAlias('sequence', 'getSequence');

    }

    /**
     * @return TransactionInput
     */
    public function __clone()
    {
        $this->script = clone $this->script;
    }

    /**
     * @return OutPointInterface
     */
    public function getOutPoint()
    {
        return $this->outPoint;
    }

    /**
     * @return Script
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
     * @param TransactionInputInterface $input
     * @return bool
     */
    public function equals(TransactionInputInterface $input)
    {
        $outPoint = $this->outPoint->equals($input->getOutPoint());
        if (!$outPoint) {
            return false;
        }

        $script = $this->script->equals($input->getScript());
        if (!$script) {
            return false;
        }

        return gmp_cmp($this->sequence, $input->getSequence()) === 0;
    }

    /**
     * Check whether this transaction is a Coinbase transaction
     *
     * @return boolean
     */
    public function isCoinbase()
    {
        $math = Bitcoin::getMath();
        $outpoint = $this->outPoint;
        return $outpoint->getTxId()->getBinary() === str_pad('', 32, "\x00")
            && $math->cmp($outpoint->getVout(), $math->hexDec('ffffffff')) === 0;
    }

    /**
     * @return bool
     */
    public function isFinal()
    {
        $math = Bitcoin::getMath();
        return $math->cmp($this->getSequence(), self::SEQUENCE_FINAL) === 0;
    }

    /**
     * @return BufferInterface
     */
    public function getBuffer()
    {
        return (new TransactionInputSerializer(new OutPointSerializer()))->serialize($this);
    }
}
