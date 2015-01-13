<?php

namespace Bitcoin\Miner;

use Bitcoin\Bitcoin;
use Bitcoin\Chain\Difficulty;
use Bitcoin\Crypto\Hash;
use Bitcoin\Util\Buffer;
use Bitcoin\Util\Parser;
use Bitcoin\Script\Script;
use Bitcoin\Script\ScriptInterface;
use Bitcoin\Transaction\Transaction;
use Bitcoin\Transaction\TransactionInterface;
use Bitcoin\Transaction\TransactionInput;
use Bitcoin\Transaction\TransactionOutput;
use Bitcoin\Block\Block;
use Bitcoin\Block\MerkleRoot;
use Bitcoin\Block\BlockHeader;
use Bitcoin\Block\BlockHeaderInterface;

/**
 * Class Miner
 * @package Bitcoin\Miner
 */
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
     * @param BlockHeaderInterface $lastBlockHeader
     * @param ScriptInterface $script
     * @param Buffer $personalString
     * @param mixed $timestamp
     */
    public function __construct(BlockHeaderInterface $lastBlockHeader, ScriptInterface $script, Buffer $personalString = null, $timestamp = null)
    {
        $this->lastBlockHeader = $lastBlockHeader;

        $this->script = $script;
        $this->math = Bitcoin::getMath();
        $this->personalString = $personalString ?: new Buffer();
        $this->timestamp = $timestamp ?: time();
        return $this;
    }

    public function setTransactions(array $transactions)
    {
        $this->transactions = $transactions;
        return $this;
    }

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
     * @throws \Exception
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

        $inputs = &$coinbaseTx->getInputsReference();
        $header = new BlockHeader();
        $block  = new Block;
        $found  = false;

        $usingDiff = $this->lastBlockHeader->getBits();
        $diff      = new Difficulty($usingDiff);
        $target    = $diff->getTarget();

        while ($found == false) {
            // Set coinbase script, and build Merkle tree & block header.
            $inputs[0]->setScript($this->getCoinbaseScriptBuf());

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
                //->setBits($this->lastBlockHeader->getBits());

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
