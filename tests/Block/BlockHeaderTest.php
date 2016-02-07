<?php

namespace BitWasp\Bitcoin\Tests\Block;

use BitWasp\Bitcoin\Block\BlockHeaderFactory;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Buffertools\Buffer;
use BitWasp\Bitcoin\Block\BlockHeader;
use BitWasp\Bitcoin\Block\BlockHeaderInterface;

class BlockHeaderTest extends AbstractTestCase
{

    private function getGenesisHex()
    {
        return '0100000000000000000000000000000000000000000000000000000000000000000000003ba3edfd7a7b12b27ac72c3e67768f617fc81bc3888a51323a9fb8aa4b1e5e4a29ab5f49ffff001d1dac2b7c';
    }

    public function testNewHeader()
    {
        //old: public function __construct($version = null, $prevBlock = null, $nextBlock = null, $merkleRoot = null, $timestamp = null, $bits = null, $nonce = null)
        $version = 2;
        $prevBlock = Buffer::hex('4141414141414141414141414141414141414141414141414141414141414141', 32);
        $merkleRoot = Buffer::hex('4242424241414141414141414141414141414141414141414141414141414141', 32);
        $time ='191230123';
        $bits = Buffer::hex('1d00ffff');
        $nonce = '666';

        $header = new BlockHeader(
            $version,
            $prevBlock,
            $merkleRoot,
            $time,
            $bits,
            $nonce
        );

        $this->assertEquals($version, $header->getVersion());
        $this->assertEquals($prevBlock, $header->getPrevBlock());
        $this->assertEquals($merkleRoot, $header->getMerkleRoot());
        $this->assertEquals($time, $header->getTimestamp());
        $this->assertEquals($nonce, $header->getNonce());
        $this->assertEquals($version, $header['version']);
        $this->assertEquals($prevBlock, $header['prevBlock']);
        $this->assertEquals($merkleRoot, $header['merkleRoot']);
        $this->assertEquals($time, $header['timestamp']);
        $this->assertEquals($nonce, $header['nonce']);

    }

    public function testGetVersionDefault()
    {
        $header = new BlockHeader(
            BlockHeaderInterface::CURRENT_VERSION,
            Buffer::hex('00000000aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa', 32),
            Buffer::hex('12340000aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa', 32),
            1,
            new Buffer(),
            1
        );

        $this->assertEquals(BlockHeaderInterface::CURRENT_VERSION, $header->getVersion());
    }

    public function testFromParser()
    {
        $result = BlockHeaderFactory::fromHex($this->getGenesisHex());

        $this->assertInstanceOf($this->headerType, $result);
        $this->assertSame('1', $result->getVersion());

        $this->assertInstanceOf($this->bufferType, $result->getPrevBlock());
        $this->assertSame('0000000000000000000000000000000000000000000000000000000000000000', $result->getPrevBlock()->getHex());

        $this->assertInstanceOf($this->bufferType, $result->getMerkleRoot());
        $this->assertSame('4a5e1e4baab89f3a32518a88c31bc87f618f76673e2cc77ab2127b7afdeda33b', $result->getMerkleRoot()->getHex());

        $this->assertInstanceOf($this->bufferType, $result->getBits());
        $this->assertSame('1d00ffff', $result->getBits()->getHex());

        $this->assertSame('1231006505', $result->getTimestamp());

        $this->assertSame('2083236893', $result->getNonce());
    }

    public function testSerialize()
    {
        $result = BlockHeaderFactory::fromHex($this->getGenesisHex());
        $this->assertSame($this->getGenesisHex(), $result->getHex());
    }

    public function testGetBlockHash()
    {
        $result = $this->getGenesisBlock()->getHeader();
        $this->assertSame('000000000019d6689c085ae165831e934ff763ae46a2a6c172b3f1b60a8ce26f', $result->getHash()->getHex());
    }

    /**
     * @expectedException \BitWasp\Buffertools\Exceptions\ParserOutOfRange
     * @expectedExceptionMessage Failed to extract full block header from parser
     */
    public function testFromParserFailure()
    {
        $genesisHeader = '0100000000000000000000003BA3EDFD7A7B12B27AC72C3E67768F617FC81BC3888A51323A9FB8AA4B1E5E4A29AB5F49FFFF001D1DAC2B7C';
        BlockHeaderFactory::fromHex($genesisHeader);
    }

    public function testFromHex()
    {
        $header = BlockHeaderFactory::fromHex($this->getGenesisHex());
        $this->assertSame('000000000019d6689c085ae165831e934ff763ae46a2a6c172b3f1b60a8ce26f', $header->getHash()->getHex());
    }
}
