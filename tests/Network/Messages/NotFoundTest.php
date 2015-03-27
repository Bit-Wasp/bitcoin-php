<?php

namespace BitWasp\Bitcoin\Tests\Network\Messages;

use BitWasp\Bitcoin\Buffer;
use BitWasp\Bitcoin\Network\Messages\NotFound;
use BitWasp\Bitcoin\Network\Structure\InventoryVector;
use BitWasp\Bitcoin\Tests\AbstractTestCase;

class NotFoundTest extends AbstractTestCase
{
    public function testNotFound()
    {
        $not = new NotFound();
        $this->assertEquals('notfound', $not->getNetworkCommand());
        $this->assertEquals(0, count($not));

        $empty = $not->getItems();
        $this->assertEquals(0, count($empty));
        $this->assertInternalType('array', $empty);

        $inv = new InventoryVector(InventoryVector::MSG_TX, Buffer::hex('4141414141414141414141414141414141414141414141414141414141414141'));
        $not->addItem($inv);
        $this->assertEquals(1, count($not));
        $this->assertEquals($inv, $not->getItem(0));
    }

    public function testNotFoundArray()
    {
        $array = [
            new InventoryVector(InventoryVector::MSG_TX, Buffer::hex('4141414141414141414141414141414141414141414141414141414141414141')),
            new InventoryVector(InventoryVector::MSG_TX, Buffer::hex('4141414141414141414141414141414141414141414141414141414141414142')),
            new InventoryVector(InventoryVector::MSG_TX, Buffer::hex('4141414141414141414141414141414141414141414141414141414141414143'))
        ];

        $not = new NotFound($array);

        $this->assertEquals($array, $not->getItems());
        $this->assertEquals(count($array), count($not));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testNotFoundFailure()
    {
        $not = new NotFound();
        $not->getItem(10);
    }
}