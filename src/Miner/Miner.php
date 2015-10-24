<?php

namespace BitWasp\Bitcoin\Miner;

use BitWasp\Bitcoin\Chain\Params;
use BitWasp\Bitcoin\Chain\ProofOfWork;
use BitWasp\Bitcoin\Crypto\Hash;
use BitWasp\Bitcoin\Math\Math;
use BitWasp\Bitcoin\Transaction\Mutator\InputMutator;
use BitWasp\Bitcoin\Transaction\TransactionFactory;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\Parser;
use BitWasp\Bitcoin\Script\Script;
use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Bitcoin\Collection\Transaction\TransactionCollection;
use BitWasp\Bitcoin\Transaction\TransactionInterface;
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
     * @var int|string
     */
    private $version;

    /**
     * @var bool
     */
    private $report;

    /**
     * @var Params
     */
    private $params;

    /**
     * @param Params $params
     * @param Math $math
     * @param BlockHeaderInterface $lastBlockHeader
     * @param ScriptInterface $script
     * @param int $timestamp
     * @param Buffer|null $personalString
     * @param int $version
     * @param bool|false $report
     */
    public function __construct(
        Params $params,
        Math $math,
        BlockHeaderInterface $lastBlockHeader,
        ScriptInterface $script,
        $timestamp,
        Buffer $personalString = null,
        $version = 1,
        $report = false
    ) {
        $this->params = $params;
        $this->math = $math;
        $this->lastBlockHeader = $lastBlockHeader;
        $this->script = $script;
        $this->personalString = $personalString ?: new Buffer('', 0, $math);
        $this->timestamp = $timestamp;
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
            ->writeWithLength(Buffer::int($this->extraNonce, 4, $this->math))
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
    public function run()
    {
        $nonce = '0';
        $maxNonce = $this->math->pow(2, 32);


        $coinbaseBuilder = TransactionFactory::build()
            ->input('0000000000000000000000000000000000000000000000000000000000000000', 0xffffffff)
            ->output(5000000000, $this->script);

        $coinbaseTx = $coinbaseBuilder->get();
        $coinbaseMutator = TransactionFactory::mutate($coinbaseTx);
        $inputsMutator = $coinbaseMutator->inputsMutator();

        $found = false;

        $usingDiff = $this->lastBlockHeader->getBits();
        $diff = new ProofOfWork($this->math, $this->params);
        $target = $diff->getTarget($usingDiff);

        while (false === $found) {
            // Set coinbase script, and build Merkle tree & block header.
            $inputsMutator->applyTo(0, function (InputMutator $m) {
                $m->script($this->getCoinbaseScriptBuf());

            });
            $coinbaseTx = $coinbaseMutator->inputs($inputsMutator->get())->get();

            $transactions = new TransactionCollection(array_merge(array($coinbaseTx), $this->transactions->all()));

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
                $hash = Hash::sha256d($header->getBuffer())->flip();

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
