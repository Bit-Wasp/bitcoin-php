<?php
/**
 * Created by PhpStorm.
 * User: thomas
 * Date: 22/11/14
 * Time: 17:18
 */


namespace Bitcoin\Util;

class ParserTest extends \PHPUnit_Framework_TestCase
{
    protected $parser;

    public function setUp()
    {
        $this->parser = new Parser();
    }

    public function testCreatesInstance()
    {
        $buffer = Buffer::hex('41414141');
        $this->parser = new Parser($buffer);
    }


    public function testNumToVarInt()
    {
        // Should not prefix with anything. Just return chr($decimal);
        for ($i = 0; $i < 253; $i++) {
            $decimal = 1;
            $expected = chr($decimal);
            $val = $this->parser->numToVarInt($decimal)->serialize();

            $this->assertSame($expected, $val);
        }
    }

    public function testNumToVarInt1LowerFailure()
    {
        // Decimal of this size does not take a prefix
        $decimal  = 0xfc; // 252;
        $prefixOp = 0xfd;
        $expected = pack("Cv", $prefixOp, $decimal);
        $val = $this->parser->numToVarInt($decimal)->serialize();

        $this->assertNotSame($expected, $val);
    }
    public function testNumToVarInt1Lowest()
    {

        // Decimal > 253 requires a prefix
        $prefixOp = 0xfd;
        $decimal  = 0xfd;
        $expected = pack("Cv", $prefixOp, $decimal);
        $val = $this->parser->numToVarInt($decimal);//->serialize();


        $this->assertSame($expected, $val->serialize());
    }
    public function testNumToVarInt1Upper()
    {
        // This prefix is used up to 0xffff, because if we go higher,
        // the prefixes are no longer in agreement

        $prefixOp = 0xfd;
        $decimal  = 0xff;
        $expected = pack("Cv", $prefixOp, $decimal);
        $val = $this->parser->numToVarInt($decimal)->serialize();
        $this->assertSame($expected, $val);
    }


    public function testNumToVarInt2LowerFailure()
    {

        // We can check that numbers this low don't yield a 0xfe prefix
        $prefixOp = 0xfe;
        $decimal  = 0xfffe;
        $expected = pack("CV", $prefixOp, $decimal);
        $val = $this->parser->numToVarInt($decimal);//->serialize();

        $this->assertNotSame($expected, $val);
    }

    public function testNumToVarInt2Lowest()
    {
        // With this prefix, check that the lowest for this field IS prefictable.
        $prefixOp = 0xfe;
        $decimal  = 256;
        $expected = pack("CV", $prefixOp, $decimal);
        $val = $this->parser->numToVarInt($decimal);//->serialize();
        $this->assertSame($expected, $val->serialize());
    }

    public function testNumToVarInt2Upper()
    {

        // Last number that will share 0xfe prefix: 2^32
        $prefixOp = 0xfe;
        $decimal  = 0xffff;
        $expected = pack("CV", $prefixOp, $decimal);
        $val = $this->parser->numToVarInt($decimal);//->serialize();

        $this->assertSame($expected, $val->serialize());
    }

    /**
     * @expectedException \Exception
     */
    public function testNumToVarIntOutOfRange()
    {
        // Check that this is out of range (PHP's fault)
        $prefixOp = 0xfe;
        $decimal  = 0xffffffff + 1;                             // 2^32 - 1
        $this->parser->numToVarInt($decimal);
    }

    /**
     * @depends testCreatesInstance
     */
    public function testGetBuffer()
    {
        $buffer = Buffer::hex('41414141');

        $this->parser = new Parser($buffer);
        $parserData = $this->parser->getBuffer()->serialize();
        $bufferData = $buffer->serialize();
    }


    /**
     * @depends testCreatesInstance
  /*   public function testGetBufferNull()
    {
        $buffer = new Buffer();
        $this->parser = new Parser($buffer);
        $parserData = $this->parser->getBuffer()->serialize();
        $bufferData = $buffer->serialize();
        $this->assertSame($parserData, $bufferData);
    }*/


} 