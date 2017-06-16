<?php

namespace BitWasp\Bitcoin\Transaction\SignatureHash;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Crypto\Hash;
use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Bitcoin\Serializer\Transaction\TransactionSerializer;
use BitWasp\Bitcoin\Serializer\Transaction\TransactionSerializerInterface;
use BitWasp\Bitcoin\Transaction\TransactionInterface;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\BufferInterface;

class Hasher extends SigHash
{
    /**
     * @var TransactionSerializerInterface
     */
    private $txSerializer;

    /**
     * Hasher constructor.
     * @param TransactionInterface $transaction
     * @param TransactionSerializerInterface|null $txSerializer
     */
    public function __construct(TransactionInterface $transaction, TransactionSerializerInterface $txSerializer = null)
    {
        $this->txSerializer = $txSerializer ?: new TransactionSerializer();
        parent::__construct($transaction);
    }

    /**
     * Calculate the hash of the current transaction, when you are looking to
     * spend $txOut, and are signing $inputToSign. The SigHashType defaults to
     * SIGHASH_ALL, though SIGHASH_SINGLE, SIGHASH_NONE, SIGHASH_ANYONECANPAY
     * can be used.
     *
     * @param ScriptInterface $txOutScript
     * @param int $inputToSign
     * @param int $sighashType
     * @return BufferInterface
     * @throws \Exception
     */
    public function calculate(ScriptInterface $txOutScript, $inputToSign, $sighashType = SigHash::ALL)
    {
        $math = Bitcoin::getMath();
        if ($inputToSign >= count($this->tx->getInputs())) {
            return Buffer::hex('0100000000000000000000000000000000000000000000000000000000000000', 32, $math);
        }

        if (($sighashType & 0x1f) == SigHash::SINGLE) {
            if ($inputToSign >= count($this->tx->getOutputs())) {
                return Buffer::hex('0100000000000000000000000000000000000000000000000000000000000000', 32, $math);
            }
        }

        $serializer = new TxSigHashSerializer($this->tx, $txOutScript, $inputToSign, $sighashType);
        $sigHashData = new Buffer($serializer->serializeTransaction() . pack('V', $sighashType));
        return Hash::sha256d($sigHashData);
    }
}
