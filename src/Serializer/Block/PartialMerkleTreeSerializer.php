<?php

namespace BitWasp\Bitcoin\Serializer\Block;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Block\PartialMerkleTree;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\Buffertools;
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

        //var_dump(str_split($vBits->getBinary(), 1));
        //var_dump($vBits);
        //Bitcoin::getMath()->baseConvert($vBits->)
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
        //$vBits = str_split(str_pad('', $size, chr('0'), STR_PAD_LEFT), 1);

        $vBits = [];

        for ($p = 0; $p < $size; $p++) {
            $byteIndex = (int)floor($p / 8);
            $byte = ord($vBytes[$byteIndex]->getBinary());
            echo $byte . "\n";
            $v =(1 << ($p % 8));
            echo " [ " . $v . "\n";

            $vBits[$p] = ($byte & (1 << ($p % 8))) != 0;
            echo $vBits[$p] . "\n";
            echo "---\n";
        }
        var_dump($vBits);
        return array_slice($vBits, 0, $last);
        return $vBits;
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
        $flipped = array_map(
            function (Buffer $value) {
                return new Buffer(Buffertools::flipBytes($value->getBinary()));
            },
            $tree->getHashes()
        );

        $padded = $this->bitsToBuffers($tree->getFlagBits());
        return $this->getTemplate()->write([
            $tree->getTxCount(),
            $flipped,
            $padded
        ]);
    }
}
