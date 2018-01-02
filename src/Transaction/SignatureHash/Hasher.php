<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Transaction\SignatureHash;

use BitWasp\Bitcoin\Crypto\Hash;
use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\BufferInterface;

class Hasher extends SigHash
{
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
    public function calculate(
        ScriptInterface $txOutScript,
        int $inputToSign,
        int $sighashType = SigHash::ALL
    ): BufferInterface {
        if ($inputToSign >= count($this->tx->getInputs())) {
            return Buffer::hex('0100000000000000000000000000000000000000000000000000000000000000', 32);
        }

        if (($sighashType & 0x1f) == SigHash::SINGLE) {
            if ($inputToSign >= count($this->tx->getOutputs())) {
                return Buffer::hex('0100000000000000000000000000000000000000000000000000000000000000', 32);
            }
        }

        $serializer = new TxSigHashSerializer($this->tx, $txOutScript, $inputToSign, $sighashType);
        $sigHashData = new Buffer($serializer->serializeTransaction() . pack('V', $sighashType));
        return Hash::sha256d($sigHashData);
    }
}
