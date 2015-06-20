<?php

namespace BitWasp\Bitcoin\Test\Network\Structure;

use BitWasp\Bitcoin\Serializer\Network\Structure\InventoryVectorSerializer;
use BitWasp\Buffertools\Buffer;
use BitWasp\Bitcoin\Network\Structure\InventoryVector;
use BitWasp\Bitcoin\Tests\AbstractTestCase;

class InventoryVectorTest extends AbstractTestCase
{
    public function testInventoryVector()
    {
        $buffer = Buffer::hex('4141414141414141414141414141414141414141414141414141414141414141');
        $inv = new InventoryVector(InventoryVector::ERROR, $buffer);
        $this->assertEquals(0, $inv->getType());
        $inv = new InventoryVector(InventoryVector::MSG_TX, $buffer);
        $this->assertEquals(1, $inv->getType());
        $inv = new InventoryVector(InventoryVector::MSG_BLOCK, $buffer);
        $this->assertEquals(2, $inv->getType());
        $inv = new InventoryVector(InventoryVector::MSG_FILTERED_BLOCK, $buffer);
        $this->assertEquals(3, $inv->getType());

        $this->assertEquals($buffer, $inv->getHash());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testInvalidType()
    {
        new InventoryVector(9, new Buffer('4141414141414141414141414141414141414141414141414141414141414141'));
    }


    /**
     * @expectedException \InvalidArgumentException
     */
    public function testInvalidLength()
    {
        new InventoryVector(InventoryVector::MSG_TX, new Buffer('41414141414141414141414141414141414141414141414141414141414141'));
    }

    public function testSerializer()
    {
        $buffer = Buffer::hex('4141414141414141414141414141414141414141414141414141414141414141');
        $inv = new InventoryVector(InventoryVector::ERROR, $buffer);

        $serializer = new InventoryVectorSerializer();
        $serialized = $inv->getBuffer();

        $parsed = $serializer->parse($serialized);
        $this->assertEquals($inv, $parsed);

    }
}
