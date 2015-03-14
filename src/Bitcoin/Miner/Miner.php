<?php

namespace Afk11\Bitcoin\Miner;

use Afk11\Bitcoin\Chain\Difficulty;
use Afk11\Bitcoin\Crypto\Hash;
use Afk11\Bitcoin\Math\Math;
use Afk11\Bitcoin\Buffer;
use Afk11\Bitcoin\Parser;
use Afk11\Bitcoin\Script\Script;
use Afk11\Bitcoin\Script\ScriptInterface;
use Afk11\Bitcoin\Transaction\TransactionCollection;
use Afk11\Bitcoin\Transaction\TransactionFactory;
use Afk11\Bitcoin\Transaction\TransactionInterface;
use Afk11\Bitcoin\Transaction\TransactionInput;
use Afk11\Bitcoin\Transaction\TransactionOutput;
use Afk11\Bitcoin\Block\Block;
use Afk11\Bitcoin\Block\MerkleRoot;
use Afk11\Bitcoin\Block\BlockHeader;
use Afk11\Bitcoin\Block\BlockHeaderInterface;

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
        return $this;
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
     * @throws \Afk11\Bitcoin\Exceptions\MerkleTreeEmpty
     */
    public function run(TransactionInterface $coinbaseTx = null)
    {
        $nonce = '0';
        $maxNonce = $this->math->pow(2, 32);

        // Allow user supplied transactions
        if ($coinbaseTx == null) {
            $input = new TransactionInput;
            $input->setTransactionId('0000000000000000000000000000000000000000000000000000000000000000');
            $input->setVout(0xffffffff);

            $output = new TransactionOutput;
            $output->setScript($this->script);
            $output->setValue(5000000000);

            $coinbaseTx = TransactionFactory::create();
            $coinbaseTx->getInputs()->addInput($input);
            $coinbaseTx->getOutputs()->addOutput($output);
        }

        $inputs = $coinbaseTx->getInputs();
        $header = new BlockHeader();
        $block  = new Block($this->math);
        $found  = false;

        $usingDiff = $this->lastBlockHeader->getBits();
        $diff      = new Difficulty($this->math);
        $target    = $diff->getTarget($usingDiff);

        while ($found == false) {
            // Set coinbase script, and build Merkle tree & block header.
            $inputs->getInput(0)->setScript($this->getCoinbaseScriptBuf());

            $transactions = array_merge(array($coinbaseTx), $this->transactions->getTransactions());
            $block->setTransactions(new TransactionCollection($transactions));

            $merkleRoot = new MerkleRoot($this->math, $block);
            $merkleHash = $merkleRoot->calculateHash();

            $header
                ->setVersion($this->version)
                ->setPrevBlock($this->lastBlockHeader->getBlockHash())
                ->setMerkleRoot($merkleHash)
                ->setTimestamp($this->timestamp)
                ->setBits($usingDiff);

            $t = microtime(true);

            // Loop through all nonces (up to 2^32). Restart after modifying extranonce.
            while ($this->math->cmp($nonce, $maxNonce) <= 0) {
                $header->setNonce($nonce++);

                $hash = (new Parser())
                    ->writeBytes(32, Hash::sha256d($header->getBuffer()->serialize()), true)
                    ->getBuffer();

                if ($this->math->cmp($hash->serialize('int'), $target) <= 0) {
                    $found = true;
                    break;
                }

                if ($this->report && $nonce % 100000 === 0) {
                    $time = microtime(true) - $t;
                    $khash = $nonce / $time / 1000;

                    echo "extraNonce[{$this->extraNonce}] nonce[{$nonce}] time[{$time}] khash/s[{$khash}] \n";
                }
            }

            // Whenever we exceed 2^32, increment extraNonce and reset $nonce
            $this->extraNonce++;
            $nonce = '0';
        }

        $block->setHeader($header);
        return $block;
    }
}
