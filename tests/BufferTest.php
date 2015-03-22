<?php

namespace BitWasp\Bitcoin\Tests\Util;

use \BitWasp\Bitcoin\Bitcoin;
use \BitWasp\Bitcoin\Buffer;
use \BitWasp\Bitcoin\Util\Math;

class BufferTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Buffer
     */
    protected $buffer;

    /**
     * @var string
     */
    protected $bufferType;

    public function __construct()
    {
        $this->bufferType = 'BitWasp\Bitcoin\Buffer';
    }

    public function setUp()
    {
        $this->buffer = null;
    }

    public function testCreateEmptyBuffer()
    {
        $this->buffer = new Buffer();
        $this->assertInstanceOf($this->bufferType, $this->buffer);
        $this->assertEmpty($this->buffer->serialize());
    }

    public function testCreateEmptyHexBuffer()
    {
        $this->buffer = Buffer::hex();
        $this->assertInstanceOf($this->bufferType, $this->buffer);
        $this->assertEmpty($this->buffer->serialize());
    }

    public function testCreateBuffer()
    {
        $hex = '80000000';
        $this->buffer = Buffer::hex($hex);
        $this->assertInstanceOf($this->bufferType, $this->buffer);
        $this->assertNotEmpty($this->buffer->serialize());
    }

    public function testCreateMaxBuffer()
    {
        $deci = 4294967295;
        $hex = Bitcoin::getMath()->decHex($deci);
        $lim = 32;
        $this->buffer = Buffer::hex($hex, $lim);
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Byte string exceeds maximum size
     */
    public function testCreateMaxBufferExceeded()
    {
        $lim = 4;
        $this->buffer = Buffer::hex('414141411', $lim);
    }

    public function testCreateHexBuffer()
    {
        $hex = '41414141';
        $this->buffer = Buffer::hex($hex);
        $this->assertInstanceOf($this->bufferType, $this->buffer);
        $this->assertNotEmpty($this->buffer->serialize());
    }

    public function testSerialize()
    {
        $hex = '41414141';
        $dec = Bitcoin::getMath()->hexDec($hex);
        $bin = pack("H*", $hex);
        $this->buffer = Buffer::hex($hex);

        // Check Binary
        $retBinary = $this->buffer->serialize();
        $this->assertSame($bin, $retBinary);

        // Check Hex
        $retHex = $this->buffer->serialize('hex');
        $this->assertSame($hex, $retHex);

        // Check Decimal
        $retInt = $this->buffer->serialize('int');
        $this->assertSame($dec, $retInt);
    }

    public function testGetSize()
    {
        $hex = '41414141';
        $bin = pack("H*", $hex);
        $this->buffer = Buffer::hex($hex);

        $hexSize = $this->buffer->getSize('hex');
        $this->assertSame($hexSize, strlen($hex));

        $binSize = $this->buffer->getSize();
        $this->assertSame($binSize, strlen($bin));
    }

    public function testGetMaxSizeDefault()
    {
        $this->buffer = Buffer::hex('41414141');
        $this->assertNull($this->buffer->getMaxSize());
    }

    public function testGetMaxSize()
    {
        $maxSize = 4;
        $this->buffer = Buffer::hex('41414141', $maxSize);
        $this->assertNotNull($this->buffer->getMaxSize());
        $this->assertSame($this->buffer->getMaxSize(), $maxSize);
    }
}
