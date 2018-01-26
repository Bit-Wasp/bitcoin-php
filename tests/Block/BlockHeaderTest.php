<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Tests\Block;

use BitWasp\Bitcoin\Block\BlockHeader;
use BitWasp\Bitcoin\Block\BlockHeaderFactory;
use BitWasp\Bitcoin\Exceptions\InvalidHashLengthException;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Buffertools\Buffer;

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
        $time = 191230123;
        $bits = 0x1d00ffff;
        $nonce = 666;

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
    }

    public function testFromParser()
    {
        $result = BlockHeaderFactory::fromHex($this->getGenesisHex());

        $this->assertInstanceOf(BlockHeader::class, $result);
        $this->assertSame(1, $result->getVersion());

        $this->assertInstanceOf(Buffer::class, $result->getPrevBlock());
        $this->assertSame('0000000000000000000000000000000000000000000000000000000000000000', $result->getPrevBlock()->getHex());

        $this->assertInstanceOf(Buffer::class, $result->getMerkleRoot());
        $this->assertSame('4a5e1e4baab89f3a32518a88c31bc87f618f76673e2cc77ab2127b7afdeda33b', $result->getMerkleRoot()->getHex());

        $this->assertInternalType('int', $result->getBits());
        $this->assertEquals(0x1d00ffff, $result->getBits());

        $this->assertEquals(1231006505, $result->getTimestamp());

        $this->assertEquals(2083236893, $result->getNonce());
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

    public function testInvalidMerkleRootSize()
    {
        $merkleRoot = new Buffer('', 31);
        $version = 2;
        $prevBlock = Buffer::hex('4141414141414141414141414141414141414141414141414141414141414141', 32);
        $time = 191230123;
        $bits = 0x1d00ffff;
        $nonce = 666;

        $this->expectException(InvalidHashLengthException::class);
        $this->expectExceptionMessage("BlockHeader merkleRoot must be a 32-byte Buffer");

        new BlockHeader($version, $prevBlock, $merkleRoot, $time, $bits, $nonce);
    }

    public function testInvalidPrevBlockSize()
    {
        $prevBlock = new Buffer('', 31);
        $version = 2;
        $merkleRoot = Buffer::hex('4141414141414141414141414141414141414141414141414141414141414141', 32);
        $time = 191230123;
        $bits = 0x1d00ffff;
        $nonce = 666;

        $this->expectException(InvalidHashLengthException::class);
        $this->expectExceptionMessage("BlockHeader prevBlock must be a 32-byte Buffer");

        new BlockHeader($version, $prevBlock, $merkleRoot, $time, $bits, $nonce);
    }

    public function testInvalidEquals()
    {
        $version = 2;
        $merkleRoot = Buffer::hex('4141414141414141414141414141414141414141414141414141414141414141', 32);
        $prevBlock = Buffer::hex('4141414141414141414141414141414141414141414141414141414141414141', 32);
        $time = 191230123;
        $bits = 0x1d00ffff;
        $nonce = 666;

        $header = new BlockHeader($version, $prevBlock, $merkleRoot, $time, $bits, $nonce);

        $this->assertTrue($header->equals(new BlockHeader($version, $prevBlock, $merkleRoot, $time, $bits, $nonce)));
        $this->assertFalse($header->equals(new BlockHeader($version, $prevBlock, $merkleRoot, $time, $bits, 123123123)));
        $this->assertFalse($header->equals(new BlockHeader($version, $prevBlock, $merkleRoot, $time, 0x1e00ffff, $nonce)));
        $this->assertFalse($header->equals(new BlockHeader($version, $prevBlock, $merkleRoot, 198274682, $bits, $nonce)));
        $this->assertFalse($header->equals(new BlockHeader($version, $prevBlock, Buffer::hex('4242424242424242424242424242424242424242424242424242424242424242', 32), $time, $bits, $nonce)));
        $this->assertFalse($header->equals(new BlockHeader($version, Buffer::hex('4242424242424242424242424242424242424242424242424242424242424242', 32), $merkleRoot, $time, $bits, $nonce)));
        $this->assertFalse($header->equals(new BlockHeader(3, $prevBlock, $merkleRoot, $time, $bits, $nonce)));
    }
}
