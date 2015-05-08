<?php

namespace BitWasp\Bitcoin\Miner;

use BitWasp\Bitcoin\Chain\Difficulty;
use BitWasp\Bitcoin\Crypto\Hash;
use BitWasp\Bitcoin\Math\Math;
use BitWasp\Bitcoin\Transaction\Transaction;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\Parser;
use BitWasp\Bitcoin\Script\Script;
use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Bitcoin\Transaction\TransactionCollection;
use BitWasp\Bitcoin\Transaction\TransactionInterface;
use BitWasp\Bitcoin\Transaction\TransactionInput;
use BitWasp\Bitcoin\Transaction\TransactionOutput;
use BitWasp\Bitcoin\Block\Block;
use BitWasp\Bitcoin\Block\MerkleRoot;
use BitWasp\Bitcoin\Block\BlockHeader;
use BitWasp\Bitcoin\Block\BlockHeaderInterface;

class Miner
{
    /**
     * @var Math
     */
    private $math;

    /**
     * @var ScriptInterface
     */
    private $script;

    /**
     * @var BlockHeaderInterface
     */
    private $lastBlockHeader;

    /**
     * @var TransactionCollection
     */
    private $transactions;

    /**
     * @var int
     */
    private $extraNonce = 0;

    /**
     * @var Buffer
     */
    private $personalString;

    /**
     * @var int
     */
    private $timestamp;

    /**
     * @var int
     */
    private $version;

    /**
     * @var bool
     */
    private $report;

    /**
     * @param Math $math
     * @param BlockHeaderInterface $lastBlockHeader
     * @param ScriptInterface $script
     * @param Buffer $personalString
     * @param mixed $timestamp
     * @param int $version
     * @param bool $report
     */
    public function __construct(
        Math $math,
        BlockHeaderInterface $lastBlockHeader,
        ScriptInterface $script,
        Buffer $personalString = null,
        $timestamp = null,
        $version = 1,
        $report = false
    ) {
        $this->math = $math;
        $this->lastBlockHeader = $lastBlockHeader;
        $this->script = $script;
        $this->personalString = $personalString ?: new Buffer();
        $this->timestamp = $timestamp ?: time();
        $this->version = $version;
        $this->report = $report;
        $this->transactions = new TransactionCollection();
    }

    /**
     * @param TransactionCollection $transactions
     * @return $this
     */
    public function setTransactions(TransactionCollection $transactions)
    {
        $this->transactions = $transactions;
        return $this;
    }

    /**
     * @return Script
     */
    public function getCoinbaseScriptBuf()
    {
        $buffer = (new Parser)
            ->writeWithLength($this->lastBlockHeader->getBits())
            ->writeWithLength(Buffer::hex($this->math->decHex($this->extraNonce)))
            ->writeWithLength($this->personalString)
            ->getBuffer();

        $script = new Script($buffer);

        return $script;
    }

    /**
     * @param TransactionInterface|null $coinbaseTx
     * @return Block
     * @throws \BitWasp\Bitcoin\Exceptions\MerkleTreeEmpty
     */
    public function run(TransactionInterface $coinbaseTx = null)
    {
        $nonce = '0';
        $maxNonce = $this->math->pow(2, 32);

        // Allow user supplied transactions
        if ($coinbaseTx == null) {
            $coinbaseTx = new Transaction();
            $coinbaseTx->getInputs()->addInput(new TransactionInput(
                '0000000000000000000000000000000000000000000000000000000000000000',
                0xffffffff
            ));
            $coinbaseTx->getOutputs()->addOutput(new TransactionOutput(
                5000000000,
                $this->script
            ));
        }

        $inputs = $coinbaseTx->getInputs();
        $found = false;

        $usingDiff = $this->lastBlockHeader->getBits();
        $diff = new Difficulty($this->math);
        $target = $diff->getTarget($usingDiff);

        while (false === $found) {
            // Set coinbase script, and build Merkle tree & block header.
            $inputs->getInput(0)->setScript($this->getCoinbaseScriptBuf());

            $transactions = new TransactionCollection(array_merge(array($coinbaseTx), $this->transactions->getTransactions()));

            $merkleRoot = new MerkleRoot($this->math, $transactions);
            $merkleHash = $merkleRoot->calculateHash();

            $header = new BlockHeader(
                $this->version,
                $this->lastBlockHeader->getBlockHash(),
                $merkleHash,
                $this->timestamp,
                $usingDiff,
                '0'
            );

            $t = microtime(true);

            // Loop through all nonces (up to 2^32). Restart after modifying extranonce.
            while ($this->math->cmp($header->getNonce(), $maxNonce) <= 0) {
                $header->setNonce($this->math->add($header->getNonce(), '1'));
                $hash = (new Parser())
                    ->writeBytes(32, Hash::sha256d($header->getBuffer()), true)
                    ->getBuffer();

                if ($this->math->cmp($hash->getInt(), $target) <= 0) {
                    $block = new Block($this->math, $header, $transactions);
                    return $block;
                }

                if ($this->report && $this->math->cmp($this->math->mod($header->getNonce(), 100000), '0') == 0) {
                    $time = microtime(true) - $t;
                    $khash = $nonce / $time / 1000;

                    echo "extraNonce[{$this->extraNonce}] nonce[{$nonce}] time[{$time}] khash/s[{$khash}] \n";
                }
            }

            // Whenever we exceed 2^32, increment extraNonce and reset $nonce
            $this->extraNonce++;
            $nonce = '0';
        }
    }
}
