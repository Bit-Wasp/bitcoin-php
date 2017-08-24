<?php

namespace BitWasp\Bitcoin\Script\Interpreter;

use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Bitcoin\Serializer\Transaction\TransactionSerializer;
use BitWasp\Bitcoin\Transaction\SignatureHash\Hasher;
use BitWasp\Bitcoin\Transaction\SignatureHash\SigHash;
use BitWasp\Bitcoin\Transaction\SignatureHash\V1Hasher;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\BufferInterface;

class BitcoinCashChecker extends Checker
{
    protected $sigHashOptionalBits = SigHash::ANYONECANPAY | SigHash::BITCOINCASH;

    /**
     * @param ScriptInterface $script
     * @param int $sigHashType
     * @param int $sigVersion
     * @return BufferInterface
     */
    public function getSigHash(ScriptInterface $script, $sigHashType, $sigVersion)
    {
        if ($sigVersion !== 0) {
            throw new \RuntimeException("SigVersion must be 0");
        }

        $cacheCheck = $sigVersion . $sigHashType . $script->getBuffer()->getBinary();
        if (!isset($this->sigHashCache[$cacheCheck])) {
            if ($sigHashType & SigHash::BITCOINCASH) {
                $hasher = new V1Hasher($this->transaction, $this->amount);
            } else {
                if ($this->hasherV0) {
                    $hasher = $this->hasherV0;
                } else {
                    $hasher = $this->hasherV0 = new Hasher($this->transaction, new TransactionSerializer());
                }
            }

            $hash = $hasher->calculate($script, $this->nInput, $sigHashType);
            $this->sigHashCache[$cacheCheck] = $hash->getBinary();
        } else {
            $hash = new Buffer($this->sigHashCache[$cacheCheck], 32, $this->adapter->getMath());
        }

        return $hash;
    }
}
