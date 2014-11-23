<?php
/**
 * Created by PhpStorm.
 * User: thomas
 * Date: 22/11/14
 * Time: 17:18
 */

namespace Bitcoin;


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