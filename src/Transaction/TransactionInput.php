<?php

namespace BitWasp\Bitcoin\Transaction;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Buffertools\Buffer;
use BitWasp\Bitcoin\Script\Script;
use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Bitcoin\Serializable;
use BitWasp\Bitcoin\Serializer\Transaction\TransactionInputSerializer;
use BitWasp\CommonTrait\FunctionAliasArrayAccess;

class TransactionInput extends Serializable implements TransactionInputInterface
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
     * @param Buffer $hashPrevOut
     * @param string $nPrevOut
     * @param ScriptInterface $script
     * @param int $sequence
     */
    public function __construct(Buffer $hashPrevOut, $nPrevOut, ScriptInterface $script, $sequence = self::SEQUENCE_FINAL)
    {
        if ($hashPrevOut->getSize() !== 32) {
            throw new \InvalidArgumentException('TransactionInput: hash must be a hex string');
        }

        if (!is_numeric($nPrevOut)) {
            throw new \InvalidArgumentException('TransactionInput: vout must be numeric');
        }

        if (!is_numeric($sequence)) {
            throw new \InvalidArgumentException('TransactionInput: sequence must be numeric');
        }

        $this->hashPrevOut = $hashPrevOut;
        $this->nPrevOut = $nPrevOut;
        $this->script = $script;
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
     * @return Buffer
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
        return $this->getTransactionId()->getBinary() === str_pad('', 32, "\x00")
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
