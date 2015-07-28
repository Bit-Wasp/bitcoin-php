<?php

namespace BitWasp\Bitcoin\Serializer\Block;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Block\PartialMerkleTree;
use BitWasp\Buffertools\Buffer;
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
                return $parser->readBytes(32);
            })
            ->vector(function (Parser & $parser) {
                return $parser->readBytes(1);
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
            (int)$txCount,
            $vHash,
            $this->buffersToBitArray($txCount, $vBits)
        );
    }

    public function bitsToBuffers(array $bits)
    {
        $math = Bitcoin::getMath();
        $vBytes = str_split(str_pad('', (count($bits)+7)/8, '0', STR_PAD_LEFT));
        $nBits = count($bits);

        for ($p = 0; $p < $nBits; $p++) {
            $index = (int)floor($p / 8);
            $vBytes[$index] |= $bits[$p] << ($p % 8);
        }

        $results = array_map(
            function ($value) use ($math) {
                return Buffer::hex($math->decHex($value));
            },
            $vBytes
        );
        return $results;
    }

    /**
     * @param Buffer[] $vBytes
     * @return array
     */
    public function buffersToBitArray($last, array $vBytes)
    {
        $size = count($vBytes) * 8;
        $vBits = [];

        for ($p = 0; $p < $size; $p++) {
            $byteIndex = (int)floor($p / 8);
            $byte = ord($vBytes[$byteIndex]->getBinary());
            $vBits[$p] = ($byte & (1 << ($p % 8))) != 0;
        }

        return array_slice($vBits, 0, $last);
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
            $this->bitsToBuffers($tree->getFlagBits())
        ]);
    }
}
