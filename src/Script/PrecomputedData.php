<?php declare(strict_types=1);

namespace BitWasp\Bitcoin\Script;

use BitWasp\Bitcoin\Crypto\Hash;
use BitWasp\Bitcoin\Serializer\Transaction\OutPointSerializerInterface;
use BitWasp\Bitcoin\Serializer\Transaction\TransactionOutputSerializer;
use BitWasp\Bitcoin\Transaction\TransactionInterface;
use BitWasp\Bitcoin\Transaction\TransactionOutputInterface;
use BitWasp\Buffertools\BufferInterface;

class PrecomputedData
{
    private $ready = false;
    private $spentTxOuts = [];

    private $prevoutsHash;
    private $sequencesHash;
    private $outputsHash;

    private $prevoutsSha256;
    private $sequencesSha256;
    private $outputsSha256;
    private $spentAmountsSha256;

    private $outPointSerializer;
    private $txOutSerializer;

    public function __construct(OutPointSerializerInterface $outPointSerializer, TransactionOutputSerializer $txOutSerializer)
    {
        $this->outPointSerializer = $outPointSerializer;
        $this->txOutSerializer = $txOutSerializer;
    }

    public function init(TransactionInterface $tx, array $spentOutputs = null)
    {
        if ($this->ready) {
            return;
        }
        if (null === $spentOutputs) {
            $spentOutputs = [];
        }
        if ($tx->hasWitness()) {
            $this->prevoutsSha256 = SighashGetPrevoutsSha256($this->outPointSerializer, $tx);
            $this->sequencesSha256 = SighashGetSequencesSha256($tx);
            $this->outputsSha256 = SighashGetOutputsSha256($this->txOutSerializer, $tx);

            $this->prevoutsHash = Hash::sha256($this->prevoutsSha256);
            $this->sequencesHash = Hash::sha256($this->sequencesSha256);
            $this->outputsHash = Hash::sha256($this->outputsSha256);

            if (count($spentOutputs) > 0) {
                $this->spentTxOuts = $spentOutputs;
                $this->spentAmountsSha256 = SighashGetSpentAmountsHash($this->txOutSerializer, $spentOutputs);
            }

            $this->ready = true;
        }
    }

    public function isReady(): bool
    {
        return $this->ready;
    }

    public function haveSpentOutputs(): bool
    {
        return count($this->spentTxOuts) > 0;
    }

    public function getPrevoutsSha256(): BufferInterface
    {
        return $this->prevoutsSha256;
    }
    public function getPrevoutsHash(): BufferInterface
    {
        return $this->prevoutsHash;
    }

    public function getSequencesSha256(): BufferInterface
    {
        return $this->sequencesSha256;
    }
    public function getSequencesHash(): BufferInterface
    {
        return $this->sequencesHash;
    }

    public function getOutputsSha256(): BufferInterface
    {
        return $this->outputsSha256;
    }
    public function getOutputsHash(): BufferInterface
    {
        return $this->outputsHash;
    }

    /**
     * @return TransactionOutputInterface[]
     */
    public function getSpentOutputs(): array
    {
        return $this->spentTxOuts;
    }
    public function getSpentAmountsSha256(): BufferInterface
    {
        return $this->spentAmountsSha256;
    }
}
