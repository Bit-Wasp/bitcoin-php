<?php

namespace BitWasp\Bitcoin\Network\Messages;


use BitWasp\Bitcoin\Block\BlockHeaderInterface;
use BitWasp\Bitcoin\Block\MerkleRoot;

class MerkleBlock
{
    /**
     * @var BlockHeaderInterface
     */
    private $header;

    /**
     * @var \BitWasp\Buffertools\Buffer[]
     */
    private $merkle;

    /**
     * @var
     */
    private $ntxns;

    /**
     * @param BlockHeaderInterface $header
     * @param MerkleRoot $merkle
     * @param $nTxs
     */
    public function __construct(BlockHeaderInterface $header, MerkleRoot $merkle, $nTxs)
    {
        $this->header = $header;
        $this->merkle = $merkle->calculateTree();
        $this->ntxns = $nTxs;
    }

    /**
     * @return BlockHeaderInterface
     */
    public function getHeader()
    {
        return $this->header;
    }

    /**
     * @return mixed
     */
    public function getTransactionCount()
    {
        return $this->ntxns;
    }

    /**
     * @return mixed
     */
    public function getHashes()
    {
        
    }

    /**
     *
     */
    public function getFlags()
    {

    }
}