<?php

namespace BitWasp\Bitcoin\Serializer\Block;

use BitWasp\Bitcoin\Block\FilteredBlock;
use BitWasp\Buffertools\BufferInterface;
use BitWasp\Buffertools\Buffertools;
use BitWasp\Buffertools\Parser;

class FilteredBlockSerializer
{

    /**
     * @var BlockHeaderSerializer
     */
    private $headerSerializer;

    /**
     * @var PartialMerkleTreeSerializer
     */
    private $treeSerializer;

    /**
     * @param BlockHeaderSerializer $header
     * @param PartialMerkleTreeSerializer $tree
     */
    public function __construct(BlockHeaderSerializer $header, PartialMerkleTreeSerializer $tree)
    {
        $this->headerSerializer = $header;
        $this->treeSerializer = $tree;
    }

    /**
     * @param Parser $parser
     * @return FilteredBlock
     */
    public function fromParser(Parser $parser)
    {
        return new FilteredBlock(
            $this->headerSerializer->fromParser($parser),
            $this->treeSerializer->fromParser($parser)
        );
    }

    /**
     * @param BufferInterface|string $data
     * @return FilteredBlock
     */
    public function parse($data)
    {
        return $this->fromParser(new Parser($data));
    }

    /**
     * @param FilteredBlock $merkleBlock
     * @return \BitWasp\Buffertools\BufferInterface
     */
    public function serialize(FilteredBlock $merkleBlock)
    {
        return Buffertools::concat(
            $this->headerSerializer->serialize($merkleBlock->getHeader()),
            $this->treeSerializer->serialize($merkleBlock->getPartialTree())
        );
    }
}
