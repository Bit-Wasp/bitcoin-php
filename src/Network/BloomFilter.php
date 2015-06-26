<?php

namespace BitWasp\Bitcoin\Network;

use BitWasp\Bitcoin\Crypto\Hash;
use BitWasp\Bitcoin\Flags;
use BitWasp\Bitcoin\Math\Math;
use BitWasp\Bitcoin\Script\Classifier\ScriptClassifierInterface;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Serializable;
use BitWasp\Bitcoin\Serializer\Network\BloomFilterSerializer;
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
    private $isEmpty = false;

    /**
     * @var bool
     */
    private $isFull = false;

    /**
     * @var float
     */
    private $numHashFuncs;

    /**
     * @var array
     */
    private $data = [];

    /**
     * @var int|string
     */
    private $nTweak;

    /**
     * @param Math $math
     * @param array $vFilter
     * @param int $numHashFuncs
     * @param int $nTweak
     * @param Flags $flags
     */
    public function __construct(Math $math, array $vFilter, $numHashFuncs, $nTweak, Flags $flags)
    {
        $this->math = $math;
        $this->data = $vFilter;
        $this->numHashFuncs = $numHashFuncs;
        $this->nTweak = $nTweak;
        $this->flags = $flags;
    }

    /**
     * @param $size
     * @return array
     */
    public static function emptyFilter($size)
    {
        return str_split(str_pad('', $size, '0'), 1);
    }

    /**
     * @param Math $math
     * @param int $nElements
     * @param int $nFpRate
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
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @return float
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
     * @param double $fpRate
     * @return float
     */
    public static function idealSize($nElements, $fpRate)
    {
        return floor(
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
     * @return float
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
     * @return Int
     */
    public function hash($nHashNum, Buffer $data)
    {
        return $this->math->mod(
            Hash::murmur3($data, ($nHashNum * self::TWEAK_START + $this->nTweak) & 0xffffffff)->getInt(),
            count($this->data) * 8
        );
    }

    /**
     * @param Buffer $data
     */
    public function insertData(Buffer $data)
    {
        if ($this->isFull) {
            return;
        }

        for ($i = 0; $i < $this->numHashFuncs; $i++) {
            $index = $this->hash($i, $data);
            $this->data[$index >> 3] |= (1 << (7 & $index));
        }

        $this->isEmpty = false;
    }

    /**
     * @param string $txid
     * @param int $vout
     */
    public function insertOutpoint($txid, $vout)
    {
        $this->insertData(new Buffer(pack("H64N", $txid, $vout)));
    }

    /**
     * @param string $hash
     */
    public function insertHash($hash)
    {
        $this->insertData(Buffer::hex($hash));
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

            if (!($this->data[($index >> 3)] & (1 << (7 & $index)))) {
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
        return $this->containsData(new Buffer(pack("H64N", $txid, $vout)));
    }

    /**
     * @param string $hash
     * @return bool
     */
    public function containsHash($hash)
    {
        return $this->containsData(Buffer::hex($hash, 32));
    }

    /**
     * @return bool
     */
    public function hasAcceptableSize()
    {
        return count($this->data) <= self::MAX_FILTER_SIZE && $this->numHashFuncs <= self::MAX_HASH_FUNCS;
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

        $nFlags = $this->flags->getFlags() & self::UPDATE_MASK;
        $outputs = $tx->getOutputs();

        // Check for relevant output scripts. We add the outpoint to the filter if found.
        for ($i = 0, $nOutputs = count($outputs); $i < $nOutputs; $i++) {
            $script = $outputs->getOutput($i)->getScript();
            $parser = $script->getScriptParser();
            while ($parser->next($opcode, $pushdata)) {
                if ($pushdata instanceof Buffer && $pushdata->getSize() > 0 && $this->containsData($pushdata)) {
                    $found = true;

                    if (self::UPDATE_ALL === $nFlags) {
                        $this->insertOutpoint($txHash, $i);
                    } else if (self::UPDATE_P2PUBKEY_ONLY === $nFlags) {
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

            $script = $txIn->getScript();
            $parser = $script->getScriptParser();
            while ($parser->next($opcode, $pushdata)) {
                if ($pushdata instanceof Buffer && $pushdata->getSize() > 0 && $this->containsData($pushdata)) {
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
        for ($i = 0, $size = count($this->data); $i < $size; $i++) {
            $full &= ($this->data[$i] == 0xff);
            $empty &= ($this->data[$i] == 0x0);
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
