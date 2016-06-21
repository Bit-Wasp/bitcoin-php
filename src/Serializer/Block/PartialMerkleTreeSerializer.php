<?php

namespace BitWasp\Bitcoin\Serializer\Block;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Block\PartialMerkleTree;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\BufferInterface;
use BitWasp\Buffertools\Parser;
use BitWasp\Buffertools\TemplateFactory;

class PartialMerkleTreeSerializer
{
    /**
     * @var \BitWasp\Buffertools\Template
     */
    private $template;

    /**
     * PartialMerkleTreeSerializer constructor.
     */
    public function __construct()
    {
        $this->template = $this->getTemplate();
    }

    /**
     * @return \BitWasp\Buffertools\Template
     */
    public function getTemplate()
    {
        return (new TemplateFactory())
            ->uint32le()
            ->vector(function (Parser $parser) {
                return $parser->readBytes(32);
            })
            ->vector(function (Parser $parser) {
                return $parser->readBytes(1);
            })
            ->getTemplate();
    }

    /**
     * @param int $last
     * @param BufferInterface[] $vBytes
     * @return array
     */
    private function buffersToBitArray($last, array $vBytes)
    {
        $size = count($vBytes) * 8;
        $vBits = [];

        for ($p = 0; $p < $size; $p++) {
            $byteIndex = (int)floor($p / 8);
            $byte = ord($vBytes[$byteIndex]->getBinary());
            $vBits[$p] = (int) (($byte & (1 << ($p % 8))) !== 0);
        }

        return array_slice($vBits, 0, $last);
    }

    /**
     * @param Parser $parser
     * @return PartialMerkleTree
     */
    public function fromParser(Parser $parser)
    {
        list ($txCount, $vHash, $vBits) = $this->template->parse($parser);

        return new PartialMerkleTree(
            (int)$txCount,
            $vHash,
            $this->buffersToBitArray($txCount, $vBits)
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
     * @param array $bits
     * @return array
     */
    private function bitsToBuffers(array $bits)
    {
        $math = Bitcoin::getMath();
        $vBuffers = str_split(str_pad('', (count($bits)+7)/8, '0', STR_PAD_LEFT));
        $nBits = count($bits);

        for ($p = 0; $p < $nBits; $p++) {
            $index = (int)floor($p / 8);
            $vBuffers[$index] |= $bits[$p] << ($p % 8);
        }

        foreach ($vBuffers as &$value) {
            $value = Buffer::int($value, null, $math);
        }
        unset($value);

        return $vBuffers;
    }

    /**
     * @param PartialMerkleTree $tree
     * @return \BitWasp\Buffertools\Buffer
     */
    public function serialize(PartialMerkleTree $tree)
    {
        return $this->template->write([
            $tree->getTxCount(),
            $tree->getHashes(),
            $this->bitsToBuffers($tree->getFlagBits())
        ]);
    }
}
