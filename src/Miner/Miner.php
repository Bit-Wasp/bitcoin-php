<?php

namespace Afk11\Bitcoin\Miner;

use Afk11\Bitcoin\Chain\Difficulty;
use Afk11\Bitcoin\Crypto\Hash;
use Afk11\Bitcoin\Math\Math;
use Bitcoin\Buffer;
use Bitcoin\Parser;
use Afk11\Bitcoin\Script\Script;
use Afk11\Bitcoin\Script\ScriptInterface;
use Afk11\Bitcoin\Transaction\Transaction;
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
     * @var ScriptInterface
     */
    private $script;

    /**
     * @var BlockHeaderInterface
     */
    private $lastBlockHeader;

    /**
     * @var array
     */
    private $transactions = array();

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
     * @param Math $math
     * @param BlockHeaderInterface $lastBlockHeader
     * @param ScriptInterface $script
     * @param Buffer $personalString
     * @param mixed $timestamp
     */
    public function __construct(
        Math $math,
        BlockHeaderInterface $lastBlockHeader,
        ScriptInterface $script,
        Buffer $personalString = null,
        $timestamp = null
    ) {
        $this->math = $math;
        $this->lastBlockHeader = $lastBlockHeader;
        $this->script = $script;
        $this->personalString = $personalString ?: new Buffer();
        $this->timestamp = $timestamp ?: time();
        return $this;
    }

    /**
     * @param array $transactions
     * @return $this
     */
    public function setTransactions(array $transactions)
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

            $coinbaseTx = new Transaction;
            $coinbaseTx->addInput($input);
            $coinbaseTx->addOutput($output);
        }

        $inputs = $coinbaseTx->getInputs();
        $header = new BlockHeader();
        $block  = new Block;
        $found  = false;

        $usingDiff = $this->lastBlockHeader->getBits();
        $diff      = new Difficulty($this->math);
        $target    = $diff->getTarget($usingDiff);

        while ($found == false) {
            // Set coinbase script, and build Merkle tree & block header.
            $inputs->getInput(0)->setScript($this->getCoinbaseScriptBuf());

            $transactions = array_merge(array($coinbaseTx), $this->transactions);
            $block->setTransactions($transactions);

            $merkleRoot = new MerkleRoot($block);
            $merkleHash = $merkleRoot->calculateHash();

            $header
                ->setVersion('1')
                ->setPrevBlock($this->lastBlockHeader->getBlockHash())
                ->setMerkleRoot($merkleHash)
                ->setTimestamp($this->timestamp)
                ->setBits($usingDiff);

            // Loop through all nonces (up to 2^32). Restart after modifying extranonce.
            while ($this->math->cmp($nonce, $maxNonce) <= 0) {
                $header->setNonce($nonce++);
                $hashS = Hash::sha256d($header->serialize());
                $hash = (new Parser())
                    ->writeBytes(32, $hashS, true)
                    ->getBuffer();

                if ($this->math->cmp($hash->serialize('int'), $target) <= 0) {
                    $found = true;
                    break;
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
