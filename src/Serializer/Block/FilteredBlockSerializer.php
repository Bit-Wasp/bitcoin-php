<?php

declare(strict_types=1);

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
    public function fromParser(Parser $parser): FilteredBlock
    {
        return new FilteredBlock(
            $this->headerSerializer->fromParser($parser),
            $this->treeSerializer->fromParser($parser)
        );
    }

    /**
     * @param BufferInterface $data
     * @return FilteredBlock
     */
    public function parse(BufferInterface $data): FilteredBlock
    {
        return $this->fromParser(new Parser($data));
    }

    /**
     * @param FilteredBlock $merkleBlock
     * @return BufferInterface
     */
    public function serialize(FilteredBlock $merkleBlock): BufferInterface
    {
        return Buffertools::concat(
            $this->headerSerializer->serialize($merkleBlock->getHeader()),
            $this->treeSerializer->serialize($merkleBlock->getPartialTree())
        );
    }
}
