<?php

namespace BitWasp\Bitcoin\Transaction;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Buffertools\Buffer;
use BitWasp\Bitcoin\Script\Script;
use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Bitcoin\Serializable;
use BitWasp\Bitcoin\Serializer\Transaction\TransactionInputSerializer;
use BitWasp\CommonTrait\FunctionAliasArrayAccess;

class TransactionInput extends Serializable implements TransactionInputInterface, \ArrayAccess
{
    use FunctionAliasArrayAccess;

    /**
     * @var string
     */
    private $hashPrevOut;

    /**
     * @var string|int
     */
    private $nPrevOut;

    /**
     * @var ScriptInterface
     */
    private $script;

    /**
     * @var string|int
     */
    private $sequence;

    /**
     * @param string $hashPrevOut
     * @param string $nPrevOut
     * @param ScriptInterface|Buffer $script
     * @param int $sequence
     */
    public function __construct($hashPrevOut, $nPrevOut, ScriptInterface $script = null, $sequence = self::SEQUENCE_FINAL)
    {
        if (!is_numeric($nPrevOut)) {
            throw new \InvalidArgumentException('TransactionInput: vout must be numeric');
        }

        if (!is_numeric($sequence)) {
            throw new \InvalidArgumentException('TransactionInput: sequence must be numeric');
        }

        $this->hashPrevOut = $hashPrevOut;
        $this->nPrevOut = $nPrevOut;
        $this->script = $script ?: new Script();
        $this->sequence = $sequence;
        $this
            ->initFunctionAlias('txid', 'getTransactionId')
            ->initFunctionAlias('vout', 'getVout')
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
     * Return the transaction ID buffer
     *
     * @return string
     */
    public function getTransactionId()
    {
        return $this->hashPrevOut;
    }

    /**
     * @return int
     */
    public function getVout()
    {
        return $this->nPrevOut;
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
     * Check whether this transaction is a Coinbase transaction
     *
     * @return boolean
     */
    public function isCoinbase()
    {
        $math = Bitcoin::getMath();
        return $this->getTransactionId() === '0000000000000000000000000000000000000000000000000000000000000000'
            && $math->cmp($this->getVout(), $math->hexDec('ffffffff')) === 0;
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
     * @return Buffer
     */
    public function getBuffer()
    {
        return (new TransactionInputSerializer())->serialize($this);
    }
}
