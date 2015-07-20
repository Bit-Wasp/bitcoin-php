<?php

namespace BitWasp\Bitcoin\Block;

use BitWasp\Bitcoin\Serializable;
use BitWasp\Bitcoin\Serializer\Block\HexBlockHeaderSerializer;
use BitWasp\Bitcoin\Serializer\Block\FilteredBlockSerializer;
use BitWasp\Bitcoin\Serializer\Block\PartialMerkleTreeSerializer;
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
     * Todo: Probably move this method..
     *
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
