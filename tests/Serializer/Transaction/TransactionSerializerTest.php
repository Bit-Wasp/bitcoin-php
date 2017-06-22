<?php

namespace BitWasp\Bitcoin\Tests\Serializer\Transaction;

use BitWasp\Bitcoin\Serializer\Transaction\TransactionSerializer;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Bitcoin\Transaction\TransactionFactory;

class TransactionSerializerTest extends AbstractTestCase
{
    
    public function testValidTxinVarint()
    {
        $hex = trim($this->dataFile("biginputtx.valid.txt"));
        $tx = TransactionFactory::fromHex($hex);
        $this->assertEquals(300, count($tx->getInputs()));
        
        $serialized = $tx->getHex();
        $this->assertEquals($hex, $serialized);
    }

    /**
     * @expectedException \BitWasp\Buffertools\Exceptions\ParserOutOfRange
     * @expectedExceptionMessage Insufficient data remaining for VarString
     */
    public function testInvalidTxinVarint()
    {
        // not perfect, but gotta explode somewhere
        $hex = trim($this->dataFile('biginputtx.invalid.txt'));
        TransactionFactory::fromHex($hex);
    }
}
