<?php

namespace BitWasp\Bitcoin\Bloom;

use BitWasp\Bitcoin\Crypto\Hash;
use BitWasp\Bitcoin\Flags;
use BitWasp\Bitcoin\Math\Math;
use BitWasp\Bitcoin\Serializer\Bloom\BloomFilterSerializer;
use BitWasp\Bitcoin\Script\Classifier\ScriptClassifierInterface;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Serializable;
use BitWasp\Bitcoin\Transaction\TransactionInterface;
use BitWasp\Buffertools\Buffer;

class BloomFilter extends Serializable
{
    const LN2SQUARED = '0.4804530139182014246671025263266649717305529515945455';
    const LN2 = '0.6931471805599453094172321214581765680755001343602552';
    const MAX_HASH_FUNCS = '50';
    const MAX_FILTER_SIZE = 36000; // bytes
    const TWEAK_START = '4221880213'; // 0xFBA4C795

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
    private $isEmpty = true;

    /**
     * @var bool
     */
    private $isFull = false;

    /**
     * @var int
     */
    private $numHashFuncs;

    /**
     * @var array
     */
    private $vFilter = [];

    /**
     * @var int|string
     */
    private $nTweak;

    /**
     * @var Flags
     */
    private $flags;

    /**
     * @param Math $math
     * @param array $vFilter
     * @param int $numHashFuncs
     * @param int|string $nTweak
     * @param Flags $flags
     */
    public function __construct(Math $math, array $vFilter, $numHashFuncs, $nTweak, Flags $flags)
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
    public static function emptyFilter($size)
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
     * @param Flags $flags
     * @return BloomFilter
     */
    public static function create(Math $math, $nElements, $nFpRate, $nTweak, Flags $flags)
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
     * @param int $flag
     * @return bool
     */
    public function checkFlag($flag)
    {
        return $this->math->cmp($this->math->bitwiseAnd($this->flags->getFlags(), self::UPDATE_MASK), $flag) == 0;
    }

    /**
     * @return bool
     */
    public function isUpdateNone()
    {
        return $this->checkFlag(self::UPDATE_NONE);
    }

    /**
     * @return bool
     */
    public function isUpdateAll()
    {
        return $this->checkFlag(self::UPDATE_ALL);
    }

    /**
     * @return bool
     */
    public function isUpdatePubKeyOnly()
    {
        return $this->checkFlag(self::UPDATE_P2PUBKEY_ONLY);
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->vFilter;
    }

    /**
     * @return int
     */
    public function getNumHashFuncs()
    {
        return $this->numHashFuncs;
    }

    /**
     * @return int|string
     */
    public function getTweak()
    {
        return $this->nTweak;
    }

    /**
     * @return Flags
     */
    public function getFlags()
    {
        return $this->flags;
    }

    /**
     * @param int $nElements
     * @param float $fpRate
     * @return int
     */
    public static function idealSize($nElements, $fpRate)
    {
        return (int) floor(
            bcdiv(
                min(
                    bcmul(
                        bcmul(
                            bcdiv(
                                -1,
                                self::LN2SQUARED
                            ),
                            $nElements
                        ),
                        log($fpRate)
                    ),
                    bcmul(
                        self::MAX_FILTER_SIZE,
                        8
                    )
                ),
                8
            )
        );
    }

    /**
     * @param int $filterSize
     * @param int $nElements
     * @return int
     */
    public static function idealNumHashFuncs($filterSize, $nElements)
    {
        return (int) floor(
            min(
                bcmul(
                    bcdiv(
                        bcmul(
                            $filterSize,
                            8
                        ),
                        $nElements
                    ),
                    self::LN2
                ),
                bcmul(
                    self::MAX_FILTER_SIZE,
                    8
                )
            )
        );
    }

    /**
     * @param int $nHashNum
     * @param Buffer $data
     * @return string
     */
    public function hash($nHashNum, Buffer $data)
    {
        return $this->math->mod(
            Hash::murmur3($data, ($nHashNum * self::TWEAK_START + $this->nTweak) & 0xffffffff)->getInt(),
            count($this->vFilter) * 8
        );
    }

    /**
     * @param Buffer $data
     * @return $this
     */
    public function insertData(Buffer $data)
    {
        if ($this->isFull) {
            return;
        }

        for ($i = 0; $i < $this->numHashFuncs; $i++) {
            $index = $this->hash($i, $data);
            $this->vFilter[$index >> 3] |= (1 << (7 & $index));
        }

        $this->updateEmptyFull();
        return $this;
    }

    /**
     * @param string $txid
     * @param int $vout
     * @return $this
     */
    public function insertOutpoint($txid, $vout)
    {
        return $this->insertData(new Buffer(pack("H64N", $txid, $vout)));
    }

    /**
     * @param string $hash
     * @return $this
     */
    public function insertHash($hash)
    {
        return $this->insertData(Buffer::hex($hash));
    }

    /**
     * @param Buffer $data
     * @return bool
     */
    public function containsData(Buffer $data)
    {
        if ($this->isFull) {
            return true;
        }

        if ($this->isEmpty) {
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
     * @param string $txid
     * @param int $vout
     * @return bool
     */
    public function containsUtxo($txid, $vout)
    {
        return $this->containsData(new Buffer(pack("H64N", $txid, $vout), 36, $this->math));
    }

    /**
     * @param string $hash
     * @return bool
     */
    public function containsHash($hash)
    {
        return $this->containsData(Buffer::hex($hash, 32, $this->math));
    }

    /**
     * @return bool
     */
    public function hasAcceptableSize()
    {
        return count($this->vFilter) <= self::MAX_FILTER_SIZE && $this->numHashFuncs <= self::MAX_HASH_FUNCS;
    }

    /**
     * @param TransactionInterface $tx
     * @return bool
     */
    public function isRelevantAndUpdate(TransactionInterface $tx)
    {
        $found = false;
        if ($this->isFull) {
            return true;
        }

        if ($this->isEmpty) {
            return false;
        }

        // Check if the txid hash is in the filter
        $txHash = $tx->getTransactionId();
        if ($this->containsHash($txHash)) {
            $found = true;
        }

        $outputs = $tx->getOutputs();

        // Check for relevant output scripts. We add the outpoint to the filter if found.
        for ($i = 0, $nOutputs = count($outputs); $i < $nOutputs; $i++) {
            $opCode = null;
            $pushData = new Buffer('', 0, $this->math);
            $script = $outputs->getOutput($i)->getScript();
            $parser = $script->getScriptParser();
            while ($parser->next($opCode, $pushData)) {
                if ($pushData->getSize() > 0 && $this->containsData($pushData)) {
                    $found = true;

                    if ($this->isUpdateAll()) {
                        $this->insertOutpoint($txHash, $i);
                    } else if ($this->isUpdatePubKeyOnly()) {
                        $type = ScriptFactory::scriptPubKey()->classify($script);
                        if ($type === ScriptClassifierInterface::MULTISIG || $type === ScriptClassifierInterface::PAYTOPUBKEY) {
                            $this->insertOutpoint($txHash, $i);
                        }
                    }
                }
            }
        }

        if ($found) {
            return true;
        }

        $inputs = $tx->getInputs();
        for ($i = 0, $nInputs = count($inputs); $i < $nInputs; $i++) {
            $txIn = $inputs->getInput($i);
            if ($this->containsUtxo($txIn->getTransactionId(), $txIn->getVout())) {
                return true;
            }

            $parser = $txIn->getScript()->getScriptParser();
            $opCode = null;
            $pushData = new Buffer('', 0, $this->math);
            while ($parser->next($opCode, $pushData)) {
                if ($pushData->getSize() > 0 && $this->containsData($pushData)) {
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
            $full &= ($this->vFilter[$i] == 0xff);
            $empty &= ($this->vFilter[$i] == 0x0);
        }

        $this->isFull = $full;
        $this->isEmpty = $empty;
    }

    /**
     * @return Buffer
     */
    public function getBuffer()
    {
        return (new BloomFilterSerializer())->serialize($this);
    }
}
