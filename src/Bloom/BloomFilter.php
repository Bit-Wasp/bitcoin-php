<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Bloom;

use BitWasp\Bitcoin\Crypto\Hash;
use BitWasp\Bitcoin\Math\Math;
use BitWasp\Bitcoin\Script\Classifier\OutputClassifier;
use BitWasp\Bitcoin\Serializable;
use BitWasp\Bitcoin\Serializer\Bloom\BloomFilterSerializer;
use BitWasp\Bitcoin\Transaction\OutPointInterface;
use BitWasp\Bitcoin\Transaction\TransactionInterface;
use BitWasp\Buffertools\BufferInterface;

class BloomFilter extends Serializable
{
    const LN2SQUARED = '0.4804530139182014246671025263266649717305529515945455';
    const LN2 = '0.6931471805599453094172321214581765680755001343602552';
    const MAX_HASH_FUNCS = '50';
    const MAX_FILTER_SIZE = 36000; // bytes
    const TWEAK_START = 0xFBA4C795;

    const UPDATE_NONE = 0;
    const UPDATE_ALL = 1;
    const UPDATE_P2PUBKEY_ONLY = 2;
    const UPDATE_MASK = 3;

    /**
     * @var Math
     */
    private $math;

    /**
     * @var bool
     */
    private $empty = true;

    /**
     * @var bool
     */
    private $full = false;

    /**
     * @var int
     */
    private $numHashFuncs;

    /**
     * @var array
     */
    private $vFilter = [];

    /**
     * @var int
     */
    private $nTweak;

    /**
     * @var int
     */
    private $flags;

    /**
     * @param Math $math
     * @param array $vFilter
     * @param int $numHashFuncs
     * @param int $nTweak
     * @param int $flags
     */
    public function __construct(Math $math, array $vFilter, int $numHashFuncs, int $nTweak, int $flags)
    {
        $this->math = $math;
        $this->vFilter = $vFilter;
        $this->numHashFuncs = $numHashFuncs;
        $this->nTweak = $nTweak;
        $this->flags = $flags;
        $this->updateEmptyFull();
    }

    /**
     * @param int $size
     * @return array
     */
    public static function emptyFilter(int $size): array
    {
        return str_split(str_pad('', $size, '0'), 1);
    }

    /**
     * Create the Bloom Filter given the number of elements, a false positive rate,
     * and the flags governing how the filter should be updated.
     *
     * @param Math $math
     * @param int $nElements
     * @param float $nFpRate
     * @param int $nTweak
     * @param int $flags
     * @return BloomFilter
     */
    public static function create(Math $math, int $nElements, float $nFpRate, int $nTweak, int $flags): BloomFilter
    {
        $size = self::idealSize($nElements, $nFpRate);

        return new self(
            $math,
            self::emptyFilter($size),
            self::idealNumHashFuncs($size, $nElements),
            $nTweak,
            $flags
        );
    }

    /**
     * @return bool
     */
    public function isUpdateNone(): bool
    {
        return (($this->flags & self::UPDATE_MASK) === self::UPDATE_NONE);
    }

    /**
     * @return bool
     */
    public function isUpdateAll(): bool
    {
        return (($this->flags & self::UPDATE_MASK) === self::UPDATE_ALL);
    }

    /**
     * @return bool
     */
    public function isUpdatePubKeyOnly(): bool
    {
        return (($this->flags & self::UPDATE_MASK) === self::UPDATE_P2PUBKEY_ONLY);
    }

    /**
     * @return bool
     */
    public function isEmpty(): bool
    {
        return $this->empty;
    }

    /**
     * @return bool
     */
    public function isFull(): bool
    {
        return $this->full;
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        return $this->vFilter;
    }

    /**
     * @return int
     */
    public function getNumHashFuncs(): int
    {
        return $this->numHashFuncs;
    }

    /**
     * @return int
     */
    public function getTweak(): int
    {
        return $this->nTweak;
    }

    /**
     * @return int
     */
    public function getFlags(): int
    {
        return $this->flags;
    }

    /**
     * @param int $nElements
     * @param float $fpRate
     * @return int
     */
    public static function idealSize(int $nElements, float $fpRate): int
    {
        return (int) floor(
            bcdiv(
                min(
                    bcmul(
                        bcmul(
                            bcdiv(
                                '-1',
                                (string) self::LN2SQUARED
                            ),
                            (string) $nElements
                        ),
                        (string) log($fpRate)
                    ),
                    bcmul(
                        (string) self::MAX_FILTER_SIZE,
                        '8'
                    )
                ),
                '8'
            )
        );
    }

    /**
     * @param int $filterSize
     * @param int $nElements
     * @return int
     */
    public static function idealNumHashFuncs(int $filterSize, int $nElements)
    {
        return (int) floor(
            min(
                bcmul(
                    bcdiv(
                        bcmul(
                            (string) $filterSize,
                            '8'
                        ),
                        (string) $nElements
                    ),
                    (string) self::LN2
                ),
                bcmul(
                    (string) self::MAX_FILTER_SIZE,
                    '8'
                )
            )
        );
    }

    /**
     * @param int $nHashNum
     * @param BufferInterface $data
     * @return string
     */
    public function hash(int $nHashNum, BufferInterface $data): string
    {
        $hash = Hash::murmur3($data, ($nHashNum * self::TWEAK_START + $this->nTweak) & 0xffffffff)->getInt();
        $hash = gmp_init($hash, 10);
        $hash = $this->math->mod($hash, gmp_init(count($this->vFilter) * 8));
        return gmp_strval($hash, 10);
    }

    /**
     * @param BufferInterface $data
     * @return $this
     */
    public function insertData(BufferInterface $data)
    {
        if ($this->isFull()) {
            return $this;
        }

        for ($i = 0; $i < $this->numHashFuncs; $i++) {
            $index = $this->hash($i, $data);
            $this->vFilter[$index >> 3] |= (1 << (7 & $index));
        }

        $this->updateEmptyFull();
        return $this;
    }

    /**
     * @param OutPointInterface $outPoint
     * @return BloomFilter
     */
    public function insertOutPoint(OutPointInterface $outPoint): BloomFilter
    {
        return $this->insertData($outPoint->getBuffer());
    }

    /**
     * @param BufferInterface $data
     * @return bool
     */
    public function containsData(BufferInterface $data): bool
    {
        if ($this->isFull()) {
            return true;
        }

        if ($this->isEmpty()) {
            return false;
        }

        for ($i = 0; $i < $this->numHashFuncs; $i++) {
            $index = $this->hash($i, $data);

            if (!($this->vFilter[($index >> 3)] & (1 << (7 & $index)))) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param OutPointInterface $outPoint
     * @return bool
     */
    public function containsOutPoint(OutPointInterface $outPoint): bool
    {
        return $this->containsData($outPoint->getBuffer());
    }

    /**
     * @return bool
     */
    public function hasAcceptableSize(): bool
    {
        return count($this->vFilter) <= self::MAX_FILTER_SIZE && $this->numHashFuncs <= self::MAX_HASH_FUNCS;
    }

    /**
     * @param TransactionInterface $tx
     * @return bool
     */
    public function isRelevantAndUpdate(TransactionInterface $tx): bool
    {
        $this->updateEmptyFull();
        $found = false;
        if ($this->isFull()) {
            return true;
        }

        if ($this->isEmpty()) {
            return false;
        }

        // Check if the txid hash is in the filter
        $txHash = $tx->getTxId();
        if ($this->containsData($txHash)) {
            $found = true;
        }

        $classifier = new OutputClassifier();
        
        // Check for relevant output scripts. We add the outpoint to the filter if found.
        foreach ($tx->getOutputs() as $vout => $output) {
            $script = $output->getScript();
            $parser = $script->getScriptParser();
            foreach ($parser as $exec) {
                if ($exec->isPush() && $this->containsData($exec->getData())) {
                    $found = true;
                    if ($this->isUpdateAll()) {
                        $this->insertOutPoint($tx->makeOutPoint($vout));
                    } else if ($this->isUpdatePubKeyOnly()) {
                        if ($classifier->isMultisig($script) || $classifier->isPayToPublicKey($script)) {
                            $this->insertOutPoint($tx->makeOutPoint($vout));
                        }
                    }
                }
            }
        }

        if ($found) {
            return true;
        }

        foreach ($tx->getInputs() as $txIn) {
            if ($this->containsOutPoint($txIn->getOutPoint())) {
                return true;
            }

            $parser = $txIn->getScript()->getScriptParser();
            foreach ($parser as $exec) {
                if ($exec->isPush() > 0 && $this->containsData($exec->getData())) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     *
     */
    public function updateEmptyFull()
    {
        $full = true;
        $empty = true;
        for ($i = 0, $size = count($this->vFilter); $i < $size; $i++) {
            $byte = (int) $this->vFilter[$i];
            $full &= ($byte === 0xff);
            $empty &= ($byte === 0x0);
        }

        $this->full = (bool)$full;
        $this->empty = (bool)$empty;
    }

    /**
     * @return BufferInterface
     */
    public function getBuffer(): BufferInterface
    {
        return (new BloomFilterSerializer())->serialize($this);
    }
}
