<?php

namespace BitWasp\Bitcoin\Test\Network\Structure;


use BitWasp\Bitcoin\Buffer;
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
}