<?php

namespace BitWasp\Bitcoin\Serializer\Network\Structure;

use BitWasp\Bitcoin\Network\Structure\FilteredBlock;
use BitWasp\Bitcoin\Serializer\Block\HexBlockHeaderSerializer;
use BitWasp\Bitcoin\Serializer\Network\PartialMerkleTreeSerializer;
use BitWasp\Buffertools\Buffertools;
use BitWasp\Buffertools\Parser;

class FilteredBlockSerializer
{

    /**
     * @var HexBlockHeaderSerializer
     */
    private $headerSerializer;

    /**
     * @var PartialMerkleTreeSerializer
     */
    private $treeSerializer;

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
     * @return FilteredBlock
     */
    public function fromParser(Parser & $parser)
    {
        $header = $this->headerSerializer->fromParser($parser);
        $partialTree = $this->treeSerializer->fromParser($parser);

        return new FilteredBlock(
            $header,
            $partialTree
        );
    }

    /**
     * @param $data
     * @return FilteredBlock
     */
    public function parse($data)
    {
        return $this->fromParser(new Parser($data));
    }

    /**
     * @param FilteredBlock $merkleBlock
     * @return \BitWasp\Buffertools\Buffer
     */
    public function serialize(FilteredBlock $merkleBlock)
    {
        return Buffertools::concat(
            $this->headerSerializer->serialize($merkleBlock->getHeader()),
            $this->treeSerializer->serialize($merkleBlock->getPartialTree())
        );
    }
}
