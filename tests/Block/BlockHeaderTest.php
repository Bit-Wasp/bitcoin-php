<?php

namespace BitWasp\Bitcoin\Tests\Block;

use BitWasp\Bitcoin\Block\BlockHeaderFactory;
use BitWasp\Bitcoin\Buffer;
use BitWasp\Bitcoin\Parser;
use BitWasp\Bitcoin\Block\BlockHeader;
use BitWasp\Bitcoin\Block\BlockHeaderInterface;

class BlockHeaderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var BlockHeader
     */
    protected $header;

    /**
     * @var string
     */
    protected $headerType;

    /**
     * @var string
     */
    protected $bufferType;

    public function __construct()
    {
        $this->headerType = 'BitWasp\Bitcoin\Block\BlockHeader';
        $this->bufferType = 'BitWasp\Bitcoin\Buffer';
    }

    private function getGenesisHex()
    {
        return '0100000000000000000000000000000000000000000000000000000000000000000000003ba3edfd7a7b12b27ac72c3e67768f617fc81bc3888a51323a9fb8aa4b1e5e4a29ab5f49ffff001d1dac2b7c';
    }

    public function setUp()
    {
        $this->header = new BlockHeader();
    }

    public function testCreateHeader()
    {
        $this->assertInstanceOf($this->headerType, $this->header);
    }

    public function testGetVersionDefault()
    {
        $this->assertEquals(BlockHeaderInterface::CURRENT_VERSION, $this->header->getVersion());
    }

    public function testSetVersion()
    {
        $this->header->setVersion('1');
        $this->assertEquals('1', $this->header->getVersion());
    }

    public function testGetTimestamp()
    {
        $this->assertNull($this->header->getTimestamp());
    }

    public function testSetTimestamp()
    {
        $this->header->setTimestamp('1420158469');
        $this->assertEquals('1420158469', $this->header->getTimestamp());
    }

    public function testGetNonce()
    {
        $this->assertNull($this->header->getNonce());
    }

    public function testSetNonce()
    {
        $this->header->setNonce('20229302');
        $this->assertEquals('20229302', $this->header->getNonce());
    }

    public function testGetPrevBlock()
    {
        $this->assertNull($this->header->getPrevBlock());
    }

    public function testSetPrevBlock()
    {
        $this->header->setPrevBlock('000000000019d6689c085ae165831e934ff763ae46a2a6c172b3f1b60a8ce26f');
        $this->assertEquals('000000000019d6689c085ae165831e934ff763ae46a2a6c172b3f1b60a8ce26f', $this->header->getPrevBlock());
    }

    public function testGetNextBlock()
    {
        $this->assertNull($this->header->getNextBlock());
    }

    public function testSetNextBlock()
    {
        $this->header->setNextBlock('00000000839a8e6886ab5951d76f411475428afc90947ee320161bbf18eb6048');
        $this->assertEquals('00000000839a8e6886ab5951d76f411475428afc90947ee320161bbf18eb6048', $this->header->getNextBlock());
    }

    public function testGetMerkleRoot()
    {
        $this->assertNull($this->header->getMerkleRoot());
    }

    public function testSetMerkleRoot()
    {
        $this->header->setMerkleRoot('4a5e1e4baab89f3a32518a88c31bc87f618f76673e2cc77ab2127b7afdeda33b');
        $this->assertEquals('4a5e1e4baab89f3a32518a88c31bc87f618f76673e2cc77ab2127b7afdeda33b', $this->header->getMerkleRoot());
    }

    public function testGetBits()
    {
        $this->assertNull($this->header->getBits());
    }

    public function testSetBits()
    {
        $bits = Buffer::hex('1effffff');
        $this->header->setBits($bits);
        $this->assertSame($bits, $this->header->getBits());
    }

    public function testFromParser()
    {

        $result = BlockHeaderFactory::fromHex($this->getGenesisHex());

        $this->assertInstanceOf($this->headerType, $result);
        $this->assertSame('1', $result->getVersion());

        $this->assertInstanceOf($this->bufferType, $result->getPrevBlock());
        $this->assertSame('0000000000000000000000000000000000000000000000000000000000000000', $result->getPrevBlock()->serialize('hex'));

        $this->assertInstanceOf($this->bufferType, $result->getMerkleRoot());
        $this->assertSame('4a5e1e4baab89f3a32518a88c31bc87f618f76673e2cc77ab2127b7afdeda33b', $result->getMerkleRoot()->serialize('hex'));

        $this->assertInstanceOf($this->bufferType, $result->getBits());
        $this->assertSame('1d00ffff', $result->getBits()->serialize('hex'));

        $this->assertSame('1231006505', $result->getTimestamp());

        $this->assertSame('2083236893', $result->getNonce());
    }

    public function testSerialize()
    {
        $result = BlockHeaderFactory::fromHex($this->getGenesisHex());
        $this->assertSame($this->getGenesisHex(), $result->getBuffer()->serialize('hex'));
    }

    public function testGetBlockHash()
    {
        $result = BlockHeaderFactory::fromHex($this->getGenesisHex());
        $this->assertSame('000000000019d6689c085ae165831e934ff763ae46a2a6c172b3f1b60a8ce26f', $result->getBlockHash());
    }

    /**
     * @expectedException \BitWasp\Bitcoin\Exceptions\ParserOutOfRange
     * @expectedExceptionMessage Failed to extract full block header from parser
     */
    public function testFromParserFailure()
    {
        $genesisHeader = '0100000000000000000000003BA3EDFD7A7B12B27AC72C3E67768F617FC81BC3888A51323A9FB8AA4B1E5E4A29AB5F49FFFF001D1DAC2B7C';
        $header = BlockHeaderFactory::fromHex($genesisHeader);

    }

    public function testFromHex()
    {
        $header = BlockHeaderFactory::fromHex($this->getGenesisHex());
        $this->assertSame('000000000019d6689c085ae165831e934ff763ae46a2a6c172b3f1b60a8ce26f', $header->getBlockHash());
    }
};
