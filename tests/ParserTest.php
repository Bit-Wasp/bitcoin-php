<?php

namespace BitWasp\Bitcoin\Tests;

use \BitWasp\Bitcoin\Buffer;
use \BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Network\Network;
use \BitWasp\Bitcoin\Parser;
use \BitWasp\Bitcoin\Transaction\Transaction;
use BitWasp\Bitcoin\Transaction\TransactionCollection;
use BitWasp\Bitcoin\Transaction\TransactionFactory;
use \BitWasp\Bitcoin\Transaction\TransactionInput;
use \BitWasp\Bitcoin\Transaction\TransactionOutput;

class ParserTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \BitWasp\Bitcoin\Parser
     */
    protected $parser;

    /**
     * @var string
     */
    protected $parserType;

    /**
     * @var string
     */
    protected $bufferType;

    public function __construct()
    {
        $this->parserType = 'BitWasp\Bitcoin\Parser';
        $this->bufferType = 'BitWasp\Bitcoin\Buffer';
    }

    public function setUp()
    {
        $this->parser = new Parser();
    }

    public function testParserEmpty()
    {
        $parser = new Parser();
        $this->assertInstanceOf($this->parserType, $parser);

        $this->assertSame(0, $this->parser->getPosition());
        $this->assertInstanceOf($this->bufferType, $this->parser->getBuffer());
        $this->assertEmpty($this->parser->getBuffer()->serialize('hex'));
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
        // This decimal should NOT return a prefix
        $decimal  = 0xfc; // 252;
        $val = $this->parser->numToVarInt($decimal)->serialize();
        $this->assertSame($val[0], chr(0xfc));
    }

    public function testNumToVarInt1Lowest()
    {
        // Decimal > 253 requires a prefix
        $decimal  = 0xfd;
        $expected = chr(0xfd).chr(0xfd).chr(0x00);
        $val = $this->parser->numToVarInt($decimal);//->serialize();
        $this->assertSame($expected, $val->serialize());
    }

    public function testNumToVarInt1Upper()
    {
        // This prefix is used up to 0xffff, because if we go higher,
        // the prefixes are no longer in agreement
        $decimal  = 0xffff;
        $expected = chr(0xfd) . chr(0xff) . chr(0xff);
        $val = $this->parser->numToVarInt($decimal)->serialize();
        $this->assertSame($expected, $val);
    }

    public function testNumToVarInt2LowerFailure()
    {
        // We can check that numbers this low don't yield a 0xfe prefix
        $decimal    = 0xfffe;
        $expected   = chr(0xfe) . chr(0xfe) . chr(0xff);
        $val        = $this->parser->numToVarInt($decimal);

        $this->assertNotSame($expected, $val);
    }

    public function testNumToVarInt2Lowest()
    {
        // With this prefix, check that the lowest for this field IS prefictable.
        $decimal    = 0xffff0001;
        $expected   = chr(0xfe) . chr(0x01) . chr(0x00) . chr(0xff) . chr(0xff) ;
        $val        = $this->parser->numToVarInt($decimal);

        $this->assertSame($expected, $val->serialize());
    }

    public function testNumToVarInt2Upper()
    {
        // Last number that will share 0xfe prefix: 2^32
        $decimal    = 0xffffffff;
        $expected   = chr(0xfe) . chr(0xff) . chr(0xff) . chr(0xff) . chr(0xff);
        $val        = $this->parser->numToVarInt($decimal);//->serialize();

        $this->assertSame($expected, $val->serialize());
    }

    // Varint for uint32_t

    /**
     * @expectedException \Exception
     */
    public function testNumToVarIntOutOfRange()
    {
        // Check that this is out of range (PHP's fault)
        $decimal  = Bitcoin::getMath()->pow(2, 32) + 1;
        $this->parser->numToVarInt($decimal);

    }

    /**
     * @depends testCreatesInstance
     */
    public function testGetBuffer()
    {
        $buffer = Buffer::hex('41414141');

        $this->parser = new Parser($buffer);
        $this->assertSame($this->parser->getBuffer()->serialize(), $buffer->serialize());
    }

    /**
     *
     */
    public function testGetBufferEmptyNull()
    {
        $buffer = new Buffer();
        $this->parser = new Parser($buffer);
        $parserData = $this->parser->getBuffer()->serialize();
        $bufferData = $buffer->serialize();
        $this->assertSame($parserData, $bufferData);
    }

    public function testFlipBytes()
    {
        $buffer = Buffer::hex('41');
        $string = $buffer->serialize();
        $flip   = Parser::flipBytes($string);
        $this->assertSame($flip, $string);

        $buffer = Buffer::hex('4141');
        $string = $buffer->serialize();
        $flip   = Parser::flipBytes($string);
        $this->assertSame($flip, $string);

        $buffer = Buffer::hex('4142');
        $string = $buffer->serialize();
        $flip   = Parser::flipBytes($string);
        $this->assertSame($flip, chr(0x42) . chr(0x41));

        $buffer = Buffer::hex('0102030405060708');
        $string = $buffer->serialize();
        $flip   = Parser::flipBytes($string);
        $this->assertSame($flip, chr(0x08) . chr(0x07) . chr(0x06) . chr(0x05) . chr(0x04) . chr(0x03) . chr(0x02) . chr(0x01));
    }

    public function testWriteBytes()
    {
        $bytes = '41424344';
        $parser = new Parser();
        $parser->writeBytes(4, Buffer::hex($bytes));
        $returned = $parser->getBuffer()->serialize('hex');
        $this->assertSame($returned, '41424344');
    }

    public function testWriteBytesFlip()
    {
        $bytes = '41424344';
        $parser = new Parser();
        $parser->writeBytes(4, Buffer::hex($bytes), true);
        $returned = $parser->getBuffer()->serialize('hex');
        $this->assertSame($returned, '44434241');
    }

    public function testReadBytes()
    {
        $bytes  = '41424344';
        $parser = new Parser($bytes);
        $read   = $parser->readBytes(4);
        $this->assertInstanceOf($this->bufferType, $read);
        $hex    = $read->serialize('hex');
        $this->assertSame($bytes, $hex);
    }

    public function testReadBytesFlip()
    {
        $bytes  = '41424344';
        $parser = new Parser($bytes);
        $read   = $parser->readBytes(4, true);
        $this->assertInstanceOf($this->bufferType, $read);
        $hex    = $read->serialize('hex');
        $this->assertSame('44434241', $hex);
    }

    public function testReadBytesEmpty()
    {
        // Should return false because position is zero,
        // and length is zero.

        $parser = new Parser();
        $data = $parser->readBytes(0);
        $this->assertFalse($data);
    }

    public function testReadBytesEndOfString()
    {
        $parser = new Parser('4041414142414141');
        $bytes1 = $parser->readBytes(4);
        $bytes2 = $parser->readBytes(4);
        $this->assertSame($bytes1->serialize('hex'), '40414141');
        $this->assertSame($bytes2->serialize('hex'), '42414141');
        $this->assertFalse($parser->readBytes(1));
    }

    /**
     * @expectedException \Exception
     */
    public function testReadBytesBeyondLength()
    {
        $bytes = '41424344';
        $parser = new Parser($bytes);
        $read   = $parser->readBytes(5);
    }

    public function testParseBytes()
    {
        $bytes  = '4142434445464748';
        $parser = new Parser($bytes);
        $bs1    = $parser->parseBytes(1);
        $bs2    = $parser->parseBytes(2);
        $bs3    = $parser->parseBytes(4);
        $bs4    = $parser->parseBytes(1);
        $this->assertInstanceOf($this->parserType, $bs1);
        $this->assertSame('41', $bs1->getBuffer()->serialize('hex'));
        $this->assertInstanceOf($this->parserType, $bs2);
        $this->assertSame('4243', $bs2->getBuffer()->serialize('hex'));
        $this->assertInstanceOf($this->parserType, $bs3);
        $this->assertSame('44454647', $bs3->getBuffer()->serialize('hex'));
        $this->assertInstanceOf($this->parserType, $bs4);
        $this->assertSame('48', $bs4->getBuffer()->serialize('hex'));
    }

    public function testWriteWithLength()
    {
        $str1 = Buffer::hex('01020304050607080909');
        $parser1 = new Parser();
        $parser1->writeWithLength($str1);
        $this->assertSame('0a', $parser1->readBytes(1)->serialize('hex'));
        $this->assertSame('01020304050607080909', $parser1->readBytes(10)->serialize('hex'));

        $str2 = Buffer::hex('00010203040506070809000102030405060708090001020304050607080900010203040506070809000102030405060708090001020304050607080900010203040506070809000102030405060708090001020304050607080900010203040506070809000102030405060708090001020304050607080900010203040506070809000102030405060708090001020304050607080900010203040506070809000102030405060708090001020304050607080900010203040506070809000102030405060708090001020304050607080900010203040506070809000102030405060708090001020304050607080900010203040506070809000102');
        $parser2 = new Parser();
        $parser2->writeWithLength($str2);
        $this->assertSame('fdfd00', $parser2->readBytes(3)->serialize('hex'));
        $this->assertSame('00010203040506070809', $parser2->readBytes(10)->serialize('hex'));

    }

    public function testGetVarInt()
    {
        $p1 = new Parser('0141');
        $this->assertSame('01', $p1->getVarInt()->serialize('hex'));
        $this->assertSame('41', $p1->readBytes(1)->serialize('hex'));
        $this->assertSame(false, $p1->readBytes(1));

        $p2 = new Parser('022345');
        $this->assertSame('02', $p2->getVarInt()->serialize('hex'));
        $this->assertSame('2345', $p2->readBytes(2)->serialize('hex'));
        $this->assertSame(false, $p2->readBytes(1));

        $s3 = Buffer::hex('00010203040506070809000102030405060708090001020304050607080900010203040506070809000102030405060708090001020304050607080900010203040506070809000102030405060708090001020304050607080900010203040506070809000102030405060708090001020304050607080900010203040506070809000102030405060708090001020304050607080900010203040506070809000102030405060708090001020304050607080900010203040506070809000102030405060708090001020304050607080900010203040506070809000102030405060708090001020304050607080900010203040506070809000102');
        $p3 = new Parser();
        $p3->writeWithLength($s3);
        $p3 = new Parser($p3->getBuffer());
        $this->assertSame('253', $p3->getVarInt()->serialize('int'));
    }

    public function testGetVarString()
    {
        $strings = array(
            '',
            '00',
            '00010203040506070809',
            '00010203040506070809000102030405060708090001020304050607080900010203040506070809000102030405060708090001020304050607080900010203040506070809000102030405060708090001020304050607080900010203040506070809000102030405060708090001020304050607080900010203040506070809000102030405060708090001020304050607080900010203040506070809000102030405060708090001020304050607080900010203040506070809000102030405060708090001020304050607080900010203040506070809000102030405060708090001020304050607080900010203040506070809000102'
        );

        foreach ($strings as $string) {
            $p = new Parser();
            $p->writeWithLength(Buffer::hex($string));
            $np = new Parser($p->getBuffer());
            $this->assertSame($string, $np->getVarString()->serialize('hex'));
        }
    }

    public function testGetArray()
    {
        $expected = array(
            Buffer::hex('09020304'),
            Buffer::hex('08020304'),
            Buffer::hex('07020304')
        );

        $parser   = new Parser(Buffer::hex('03090203040802030407020304'));
        $callback = function() use (&$parser) {
            return $parser->readBytes(4);
        };

        $actual   = $parser->getArray($callback);
        for ($i = 0; $i < count($expected); $i++) {
            $this->assertEquals($expected[$i]->serialize(), $actual[$i]->serialize());
        }
    }

    public function testWriteArray()
    {
        $transaction = TransactionFactory::create();
        $input  = new TransactionInput('0000000000000000000000000000000000000000000000000000000000000000', 0);
        $output = new TransactionOutput(1, null);
        $transaction
            ->getInputs()
            ->addInput($input);

        $transaction
            ->getOutputs()
            ->addOutput($output);

        $array  = new TransactionCollection(array($transaction, $transaction));
        $parser = new Parser();
        $parser->writeArray($array->getBuffer());

        $this->assertSame('010000000100000000000000000000000000000000000000000000000000000000000000000000000000ffffffff0101000000000000000000000000', $transaction->getBuffer()->serialize('hex'));
        $this->assertSame('02010000000100000000000000000000000000000000000000000000000000000000000000000000000000ffffffff0101000000000000000000000000010000000100000000000000000000000000000000000000000000000000000000000000000000000000ffffffff0101000000000000000000000000', $parser->getBuffer()->serialize('hex'));

    }

    /**
     * @expectedException \RuntimeException
     */
    public function testWriteArrayFailure()
    {
        $network = new Network('00','05','80');
        $array = array($network);

        $parser = new Parser();
        $parser->writeArray($array);
    }
} 
