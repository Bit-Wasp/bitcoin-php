<?php

namespace BitWasp\Bitcoin\Serializer\Network;

use BitWasp\Bitcoin\Network\MerkleBlock;
use BitWasp\Bitcoin\Serializer\Block\HexBlockHeaderSerializer;
use BitWasp\Buffertools\Buffertools;
use BitWasp\Buffertools\Parser;

class MerkleBlockSerializer
{
    /**
     * @var PartialMerkleTreeSerializer
     */
    private $treeSerializer;

    /**
     * @var HexBlockHeaderSerializer
     */
    private $headerSerializer;

    /**
     * @param HexBlockHeaderSerializer $header
     * @param PartialMerkleTreeSerializer $tree
     */
    public function __construct(HexBlockHeaderSerializer $header, PartialMerkleTreeSerializer $tree)
    {
        $this->headerSerializer = $header;
        $this->treeSerializer = $tree;
    }

    /**
     * @param Parser $parser
     * @return MerkleBlock
     */
    public function fromParser(Parser & $parser)
    {
        $header = $this->headerSerializer->fromParser($parser);
        $partialTree = $this->treeSerializer->fromParser($parser);

        return new MerkleBlock(
            $header,
            $partialTree
        );
    }

    /**
     * @param $data
     * @return MerkleBlock
     */
    public function parse($data)
    {
        return $this->fromParser(new Parser($data));
    }

    /**
     * @param MerkleBlock $merkleBlock
     * @return \BitWasp\Buffertools\Buffer
     */
    public function serialize(MerkleBlock $merkleBlock)
    {
        return Buffertools::concat(
            $this->headerSerializer->serialize($merkleBlock->getHeader()),
            $this->treeSerializer->serialize($merkleBlock->getPartialTree())
        );
    }
}
