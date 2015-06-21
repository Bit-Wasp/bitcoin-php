<?php

namespace BitWasp\Bitcoin\Tests\Chain;

use BitWasp\Bitcoin\Block\BlockFactory;
use BitWasp\Bitcoin\Chain\BlockHashIndex;
use BitWasp\Bitcoin\Chain\BlockHeightIndex;
use BitWasp\Bitcoin\Chain\BlockIndex;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use Doctrine\Common\Cache\ArrayCache;

class BlockIndexTest extends AbstractTestCase
{
    public function getGenesis()
    {
        $txHex = '01000000'.
            '01'.
            '0000000000000000000000000000000000000000000000000000000000000000FFFFFFFF'.
            '4D'.
            '04FFFF001D0104455468652054696D65732030332F4A616E2F32303039204368616E63656C6C6F72206F6E206272696E6B206F66207365636F6E64206261696C6F757420666F722062616E6B73'.
            'FFFFFFFF'.
            '01'.
            '00F2052A01000000'.
            '43'.
            '4104678AFDB0FE5548271967F1A67130B7105CD6A828E03909A67962E0EA1F61DEB649F6BC3F4CEF38C4F35504E51EC112DE5C384DF7BA0B8D578A4C702B6BF11D5FAC'.
            '00000000';

        $blockHex = '01000000'.
            '0000000000000000000000000000000000000000000000000000000000000000' .
            '3BA3EDFD7A7B12B27AC72C3E67768F617FC81BC3888A51323A9FB8AA4B1E5E4A' .
            '29AB5F49'.
            'FFFF001D'.
            '1DAC2B7C'.
            '01'.
            $txHex;

        $newBlock = BlockFactory::fromHex($blockHex);
        return $newBlock;
    }

    public function testIndices()
    {
        $hashIndex = new BlockHashIndex(new ArrayCache());
        $heightIndex = new BlockHeightIndex(new ArrayCache());
        $index = new BlockIndex(
            $hashIndex,
            $heightIndex
        );

        $this->assertSame($hashIndex, $index->hash());
        $this->assertSame($heightIndex, $index->height());
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Index not initialized with genesis block
     */
    public function testErrorsBeforeGenesis()
    {
        $hashIndex = new BlockHashIndex(new ArrayCache());
        $heightIndex = new BlockHeightIndex(new ArrayCache());
        $index = new BlockIndex(
            $hashIndex,
            $heightIndex
        );

        $index->height()->height();
    }

    public function testWithGenesis()
    {
        $hashIndex = new BlockHashIndex(new ArrayCache());
        $heightIndex = new BlockHeightIndex(new ArrayCache());
        $index = new BlockIndex(
            $hashIndex,
            $heightIndex
        );

        $genesis = $this->getGenesis();
        $header = $genesis->getHeader();
        $hash = $header->getBlockHash();
        $height = 0;

        $index->saveGenesis($header);
        $this->assertEquals(0, $index->height()->height());

        $this->assertEquals($hash, $index->hash()->fetch($height));
        $this->assertEquals($height, $index->height()->fetch($hash));
    }
}
