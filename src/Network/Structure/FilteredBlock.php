<?php

namespace BitWasp\Bitcoin\Network\Structure;

use BitWasp\Bitcoin\Block\BlockHeaderInterface;
use BitWasp\Bitcoin\Block\BlockInterface;
use BitWasp\Bitcoin\Network\BloomFilter;
use BitWasp\Bitcoin\Network\PartialMerkleTree;
use BitWasp\Bitcoin\Serializable;
use BitWasp\Bitcoin\Serializer\Block\HexBlockHeaderSerializer;
use BitWasp\Bitcoin\Serializer\Network\Structure\FilteredBlockSerializer;
use BitWasp\Bitcoin\Serializer\Network\PartialMerkleTreeSerializer;
use BitWasp\Buffertools\Buffer;

class FilteredBlock extends Serializable
{
    /**
     * @var BlockHeaderInterface
     */
    private $header;

    /**
     * @var PartialMerkleTree
     */
    private $partialTree;

    /**
     * @param BlockHeaderInterface $header
     * @param PartialMerkleTree $merkleTree
     */
    public function __construct(BlockHeaderInterface $header, PartialMerkleTree $merkleTree)
    {
        $this->header = $header;
        $this->partialTree = $merkleTree;
    }

    /**
     * @return BlockHeaderInterface
     */
    public function getHeader()
    {
        return $this->header;
    }

    /**
     * @return PartialMerkleTree
     */
    public function getPartialTree()
    {
        return $this->partialTree;
    }

    /**
     * @param BlockInterface $block
     * @param BloomFilter $filter
     * @return FilteredBlock
     */
    public static function filter(BlockInterface $block, BloomFilter $filter)
    {
        $vMatch = [];
        $vHashes = [];

        $txns = $block->getTransactions();
        for ($i = 0, $txCount = count($txns); $i < $txCount; $i++) {
            $tx = $txns->getTransaction($i);
            $vMatch[] = $filter->isRelevantAndUpdate($tx);

            $txid = $tx->getTransactionId();
            $vHashes[] = Buffer::hex($txid);
        }

        return new FilteredBlock(
            $block->getHeader(),
            new PartialMerkleTree(
                $txCount,
                $vHashes,
                $vMatch
            )
        );
    }

    /**
     * @param BlockInterface $block
     * @param Buffer[] $vTxid
     * @return FilteredBlock
     */
    public static function transactions(BlockInterface $block, array $vTxid)
    {
        $vMatch = [];
        $vHashes = [];

        $txns = $block->getTransactions();
        for ($i = 0, $txCount = count($txns); $i < $txCount; $i++) {
            $tx = $txns->getTransaction($i);
            $txid = Buffer::hex($tx->getTransactionId());
            $vMatch[] = in_array($txid, $vTxid);
            $vHashes[] = $txid;
        }

        return new FilteredBlock(
            $block->getHeader(),
            new PartialMerkleTree(
                $txCount,
                $vHashes,
                $vMatch
            )
        );
    }

    /**
     * @return Buffer
     */
    public function getBuffer()
    {
        return (new FilteredBlockSerializer(new HexBlockHeaderSerializer(), new PartialMerkleTreeSerializer()))->serialize($this);
    }
}
