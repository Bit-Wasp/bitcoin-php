<?php

namespace BitWasp\Bitcoin\Serializer\Network;

use BitWasp\Bitcoin\Network\PartialMerkleTree;
use BitWasp\Buffertools\Parser;
use BitWasp\Buffertools\TemplateFactory;

class PartialMerkleTreeSerializer
{
    /**
     * @return \BitWasp\Buffertools\Template
     */
    public function getTemplate()
    {
        return (new TemplateFactory())
            ->uint32le()
            ->vector(function (Parser & $parser) {
                return $parser->readBytes(32, true);
            })
            ->vector(function (Parser & $parser) {
                return$parser->readBytes(1);
            })
            ->getTemplate();
    }

    /**
     * @param Parser $parser
     * @return PartialMerkleTree
     */
    public function fromParser(Parser & $parser)
    {
        list ($txCount, $vHash, $vBits) = $this->getTemplate()->parse($parser);

        return new PartialMerkleTree(
            $txCount,
            $vHash,
            $vBits
        );
    }

    /**
     * @param $data
     * @return PartialMerkleTree
     */
    public function parse($data)
    {
        return $this->fromParser(new Parser($data));
    }

    /**
     * @param PartialMerkleTree $tree
     * @return \BitWasp\Buffertools\Buffer
     */
    public function serialize(PartialMerkleTree $tree)
    {
        return $this->getTemplate()->write([
            $tree->getTxCount(),
            $tree->getHashes(),
            $tree->getFlagBits()
        ]);
    }
}
